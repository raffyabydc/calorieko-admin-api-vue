<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * Enforce Super Admin Only logic.
     */
    private function requireSuperAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'Super Admin') {
            abort(403, 'Permission denied. Only Super Admins can access this resource.');
        }
    }

    /**
     * GET /api/admin/moderators
     * List all admins and moderators.
     */
    public function index(Request $request): JsonResponse
    {
        $this->requireSuperAdmin($request);

        // Fetch all, filter in-memory if needed, but since it's an admin page
        // returning them all is fine. The model will decrypt the fields automatically.
        $admins = User::all()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'is_active' => $u->is_active,
                'created_at' => $u->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($admins);
    }

    /**
     * POST /api/admin/moderators
     * Create a new moderator.
     */
    public function store(Request $request): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        // In-memory check for existing email
        $existing = User::all()->first(function ($u) use ($request) {
            return strtolower($u->email) === strtolower($request->email);
        });

        if ($existing) {
            return response()->json(['error' => 'An administrator with this email already exists.'], 422);
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Moderator',
            'is_active' => true,
        ]);

        SystemLog::log(
            $request->user()->email,
            'Created Moderator',
            "Moderator created: {$request->email}",
            'Success',
            $request->ip(),
            "Super Admin created a new Moderator account."
        );

        return response()->json([
            'message' => 'Moderator account created successfully.',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'is_active' => $admin->is_active,
                'created_at' => $admin->created_at->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }

    /**
     * PUT /api/admin/moderators/{id}/toggle
     * Toggle active status of a moderator.
     */
    public function toggle(Request $request, $id): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $admin = User::findOrFail($id);

        if ($admin->role === 'Super Admin' && $admin->id === $request->user()->id) {
            return response()->json(['error' => 'You cannot deactivate your own Super Admin account.'], 403);
        }

        $admin->is_active = !$admin->is_active;
        $admin->save();

        $action = $admin->is_active ? 'Reactivated' : 'Restricted/Deactivated';
        
        SystemLog::log(
            $request->user()->email,
            "{$action} Moderator",
            "Target: {$admin->email}",
            'Success',
            $request->ip(),
            "Super Admin {$action} a Moderator."
        );

        return response()->json([
            'message' => "Moderator {$action} successfully.",
            'is_active' => $admin->is_active
        ]);
    }

    /**
     * DELETE /api/admin/moderators/{id}
     * Delete a moderator.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $admin = User::findOrFail($id);

        if ($admin->role === 'Super Admin') {
            return response()->json(['error' => 'Super Admin accounts cannot be deleted.'], 403);
        }

        $email = $admin->email;
        $admin->delete();

        SystemLog::log(
            $request->user()->email,
            "Deleted Moderator",
            "Target: {$email}",
            'Success',
            $request->ip(),
            "Super Admin permanently deleted a Moderator account."
        );

        return response()->json(['message' => 'Moderator account deleted successfully.']);
    }
}
