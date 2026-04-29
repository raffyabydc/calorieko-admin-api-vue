<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates admin panel requests using Laravel Sanctum tokens.
 *
 * Verifies:
 *  1. A valid Sanctum token exists on the request.
 *  2. The authenticated user has an administrative role.
 *  3. The authenticated user's account is active.
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Verify Sanctum authentication
        if (!$user) {
            return response()->json(['error' => 'Unauthorized — no valid token provided.'], 401);
        }

        // 2. Verify the user has an admin role
        $allowedRoles = ['Super Admin', 'Moderator'];
        if (!in_array($user->role, $allowedRoles, true)) {
            return response()->json(['error' => 'Forbidden — insufficient privileges.'], 403);
        }

        // 3. Verify the account is active
        if (!$user->is_active) {
            return response()->json(['error' => 'Forbidden — account deactivated.'], 403);
        }

        return $next($request);
    }
}
