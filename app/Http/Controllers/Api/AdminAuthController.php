<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\SystemLog;
use App\Models\User;

/**
 * Handles admin panel authentication via Laravel Sanctum.
 */
class AdminAuthController extends Controller
{
    /**
     * POST /api/admin/login
     */
    public function login(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Login attempt', ['email' => $request->email]);
        
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Because the 'email' field is encrypted for data privacy (PII), 
        // we cannot use a direct SQL WHERE clause. We must filter in-memory.
        $user = User::all()->first(function ($u) use ($request) {
            return trim(strtolower($u->email)) === trim(strtolower($request->email));
        });

        \Illuminate\Support\Facades\Log::info('User found?', ['found' => $user !== null]);

        if ($user) {
            $pwMatch = Hash::check($request->password, $user->password);
            \Illuminate\Support\Facades\Log::info('Password match?', ['match' => $pwMatch]);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            SystemLog::log($request->email, 'Admin Login', null, 'Failed', $request->ip(), 'Invalid email or password.');
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        if (!$user->is_active) {
            SystemLog::log($request->email, 'Admin Login', null, 'Failed', $request->ip(), 'Account deactivated.');
            return response()->json(['error' => 'Your account has been deactivated.'], 403);
        }

        // Generate Sanctum token
        $token = $user->createToken('admin-token')->plainTextToken;

        SystemLog::log($user->email, 'Admin Login', null, 'Success', $request->ip(), 'Admin logged in successfully.');

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'email'   => $user->email,
            'role'    => $user->role,
        ]);
    }

    /**
     * POST /api/admin/logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            SystemLog::log($user->email, 'Admin Logout', null, 'Success', $request->ip(), 'Admin logged out.');
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * POST /api/admin/verify
     */
    public function verify(Request $request)
    {
        // Handled by Sanctum middleware. If we reach here, it's valid.
        $user = $request->user();
        
        return response()->json([
            'valid' => true, 
            'email' => $user->email,
            'role'  => $user->role
        ]);
    }

    /**
     * PUT /api/admin/password
     * Allows an authenticated admin/moderator to change their password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Your current password does not match our records.'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        SystemLog::log($user->email, 'Password Change', null, 'Success', $request->ip(), 'Admin successfully changed their password.');

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
