<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    /**
     * Sync: Upsert a user profile from the mobile app.
     */
    public function sync(Request $request): JsonResponse
    {
        \Log::info('Profile Sync Request:', $request->all());
        
        try {
            $data = $request->validate([
                'uid'           => 'required|string',
                'name'          => 'required|string',
                'email'         => 'required|string|email',
                'age'           => 'required|integer',
                'weight'        => 'required|numeric',
                'height'        => 'required|numeric',
                'sex'           => 'nullable|string',
                'activityLevel' => 'nullable|string',
                'goal'          => 'required|string',
                'streak'        => 'nullable|integer',
                'level'         => 'nullable|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Profile Sync Validation Failed:', $e->errors());
            throw $e;
        }

        // Ensure the authenticated user matches the profile uid
        if ($request->firebaseUid !== $data['uid']) {
            \Log::error("UID Mismatch: Firebase {$request->firebaseUid} vs Request {$data['uid']}");
            return response()->json(['error' => 'UID mismatch'], 403);
        }

        $profile = UserProfile::updateOrCreate(
            ['uid' => $data['uid']],
            $data
        );

        \Log::info('Profile synced successfully for UID: ' . $data['uid']);
        return response()->json($profile, 200);
    }

    /**
     * Admin: List all user profiles.
     * PII (name, email) is stripped and replaced with a pseudo-anonymous display_id.
     */
    public function index(): JsonResponse
    {
        // Returns all profiles, anonymized, and orders active (not suspended) users first.
        $profiles = UserProfile::orderByDesc('is_active')->get()->map(fn ($p) => $this->anonymize($p));
        return response()->json($profiles);
    }

    /**
     * Admin: Show a single user profile.
     * PII (name, email) is stripped and replaced with a pseudo-anonymous display_id.
     */
    public function show(string $uid): JsonResponse
    {
        $profile = UserProfile::findOrFail($uid);
        return response()->json($this->anonymize($profile));
    }

    /**
     * Return a privacy-safe representation of a user profile.
     * Replaces name/email with a stable pseudo-anonymous display_id.
     * Includes computed `has_recent_activity` and `days_active_last_7` fields
     * so the frontend can render Active/Dormant badges consistently with KPI tiles.
     */
    private function anonymize(UserProfile $profile): array
    {
        $sevenDaysAgoMs = (int) (now()->subDays(7)->timestamp * 1000);

        // Check if this user has ANY meal or activity logged in the last 7 days
        $hasMeals = \App\Models\MealLog::where('uid', $profile->uid)
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->exists();
        $hasActivities = \Illuminate\Support\Facades\DB::table('activity_log_table')
            ->where('uid', $profile->uid)
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->exists();
        $hasRecentActivity = $hasMeals || $hasActivities;

        // Count distinct days with ANY interaction (meals OR activities) in the last 7 days.
        // Uses a UNION query to merge dates from both tables, then counts unique dates.
        $weekAgoEpochDay = (int) floor(now()->subDays(7)->timestamp / 86400);
        $todayEpochDay   = (int) floor(now()->timestamp / 86400);

        // Nutrition summary dates (stored as epoch_day integers)
        $nutritionDates = \App\Models\DailyNutritionSummary::where('uid', $profile->uid)
            ->where('date_epoch_day', '>=', $weekAgoEpochDay)
            ->pluck('date_epoch_day')
            ->map(fn($d) => (string) $d);

        // Activity log dates (stored as epoch_millis → convert to epoch_day)
        $activityDates = \Illuminate\Support\Facades\DB::table('activity_log_table')
            ->where('uid', $profile->uid)
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->pluck('timestamp')
            ->map(fn($ts) => (string) ((int) floor($ts / 86400000)));

        // Merge both collections, keep only unique epoch_days
        $daysActiveCount = $nutritionDates
            ->merge($activityDates)
            ->unique()
            ->count();

        return [
            'uid'                  => $profile->uid,
            'display_id'           => 'Participant-' . strtoupper(substr($profile->uid, 0, 5)),
            'age'                  => $profile->age,
            'sex'                  => $profile->sex,
            'weight'               => $profile->weight,
            'height'               => $profile->height,
            'activityLevel'        => $profile->activityLevel,
            'goal'                 => $profile->goal,
            'streak'               => $profile->streak,
            'level'                => $profile->level,
            'is_active'            => $profile->is_active,
            'is_engaged'           => $hasRecentActivity && $profile->is_active,
            'has_recent_activity'  => $hasRecentActivity,
            'days_active_last_7'   => $daysActiveCount,
            'mobile_updated_at'    => $profile->mobile_updated_at ?? null,
            'updated_at'           => $profile->updated_at?->toISOString(),
        ];
    }

    /**
     * Admin: Toggle active status.
     */
    public function deactivate(string $uid): JsonResponse
    {
        $profile = UserProfile::findOrFail($uid);
        $profile->is_active = !$profile->is_active;
        $profile->save();

        // Sync status to Firebase Authentication
        try {
            if ($profile->is_active) {
                app('firebase.auth')->enableUser($uid);
            } else {
                app('firebase.auth')->disableUser($uid);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to sync Firebase Auth status for {$uid}: " . $e->getMessage());
        }

        $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
        $statusStr = $profile->is_active ? 'Reactivated' : 'Deactivated';
        SystemLog::log($adminEmail, "User {$statusStr}", "User UID: {$uid}", 'Success', request()->ip(), "Admin {$statusStr} user {$profile->email}.");

        return response()->json([
            'message' => 'User status updated successfully.',
            'is_active' => $profile->is_active
        ]);
    }

    /**
     * Admin: Trigger password reset.
     */
    public function resetPassword(string $uid): JsonResponse
    {
        $profile = UserProfile::findOrFail($uid);
        
        try {
            // Using Firebase Identity Toolkit REST API directly to bypass OAuth signature issues
            $apiKey = config('services.firebase.web_api_key');
            $response = \Illuminate\Support\Facades\Http::post("https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key={$apiKey}", [
                'requestType' => 'PASSWORD_RESET',
                'email' => $profile->email
            ]);

            if (!$response->successful()) {
                throw new \Exception($response->json('error.message', 'Unknown Firebase API Error'));
            }

            $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
            SystemLog::log($adminEmail, 'Password Reset', "User UID: {$uid}", 'Success', request()->ip(), "Admin sent password reset link to {$profile->email}.");
            
            \Log::info("Password reset sent for UID: {$uid}");
            return response()->json([
                'message' => "Password reset sequence triggered for {$profile->email}."
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to send password reset via REST: " . $e->getMessage());
            return response()->json(['error' => 'Failed to send password reset link: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Delete user from registry.
     */
    public function destroy(string $uid): JsonResponse
    {
        $profile = UserProfile::findOrFail($uid);
        $email = $profile->email;

        // Manually cascade deletes to prevent orphaned data from padding Dashboard KPIs
        \App\Models\MealLog::where('uid', $uid)->delete(); // MySQL engine will automatically cascade this to MealLogItems
        \App\Models\ActivityLog::where('uid', $uid)->delete();
        \App\Models\DailyNutritionSummary::where('uid', $uid)->delete();

        $profile->delete();

        // Delete from Firebase DB as well
        try {
            app('firebase.auth')->deleteUser($uid);
        } catch (\Exception $e) {
            \Log::error("Failed to delete user from Firebase Auth {$uid}: " . $e->getMessage());
        }

        $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
        SystemLog::log($adminEmail, 'Deleted User', "User UID: {$uid}", 'Success', request()->ip(), "Admin deleted user {$email}.");

        \Log::info("User profile deleted for UID: {$uid}");
        
        return response()->json([
            'message' => 'User successfully deleted.'
        ]);
    }
}
