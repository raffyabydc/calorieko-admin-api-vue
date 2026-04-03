<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncFullRequest;
use App\Models\UserProfile;
use App\Models\MealLog;
use App\Models\MealLogItem;
use App\Models\ActivityLog;
use App\Models\DailyNutritionSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MobileSyncController extends Controller
{
    /**
     * Handle the full atomic sync transaction from the mobile app.
     */
    public function syncFull(SyncFullRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $uid = $payload['uid'];

        // Strict UID Validation: Reject if the token's UID doesn't match the payload
        // Also reject if the payload's inner uids don't match the token
        if ($request->firebaseUid !== $uid) {
            Log::error("SyncFull UID Mismatch: Firebase {$request->firebaseUid} vs Request {$uid}");
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: UID mismatch'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $syncTimestamp = Carbon::now()->timestamp;

            // 1. Sync User Profile
            if (!empty($payload['profile'])) {
                $profileData = $payload['profile'];
                $profileData['uid'] = $uid; // Ensure UID integrity
                UserProfile::updateOrCreate(
                    ['uid' => $uid],
                    $profileData
                );
            }

            // 2. Sync Meals (Batch Upsert Logic)
            if (!empty($payload['meals'])) {
                foreach ($payload['meals'] as $mealData) {
                    if ($mealData['uid'] !== $uid) continue; // Skip bad data
                    
                    // Scope-replace updateOrCreate for the parent MealLog
                    $mealLog = MealLog::updateOrCreate(
                        ['uid' => $uid, 'timestamp' => $mealData['timestamp']],
                        [
                            'meal_type' => $mealData['meal_type'],
                            'notes' => $mealData['notes'] ?? null,
                        ]
                    );

                    // Drop existing line items to prevent duplicate append
                    $mealLog->items()->delete();

                    // Chunk insert new meal line items
                    $itemsToInsert = [];
                    foreach ($mealData['items'] as $itemData) {
                        $itemData['meal_log_id'] = $mealLog->meal_log_id;
                        $itemData['created_at'] = Carbon::now();
                        $itemData['updated_at'] = Carbon::now();
                        $itemsToInsert[] = $itemData;
                    }
                    
                    // Use insert for bulk performance
                    if (!empty($itemsToInsert)) {
                        MealLogItem::insert($itemsToInsert);
                    }
                }
            }

            // 3. Sync Activities
            if (!empty($payload['activities'])) {
                foreach ($payload['activities'] as $activityData) {
                    if ($activityData['uid'] !== $uid) continue;
                    
                    // Laravel 10+ upsert logic
                    ActivityLog::updateOrCreate(
                        ['uid' => $uid, 'timestamp' => $activityData['timestamp']],
                        $activityData
                    );
                }
            }

            // 4. Sync Daily Nutrition Summaries
            if (!empty($payload['nutrition_summaries'])) {
                foreach ($payload['nutrition_summaries'] as $summaryData) {
                    if ($summaryData['uid'] !== $uid) continue;
                    
                    DailyNutritionSummary::updateOrCreate(
                        ['uid' => $uid, 'date_epoch_day' => $summaryData['date_epoch_day']],
                        $summaryData
                    );
                }
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Data synced successfully',
                'last_successful_sync' => $syncTimestamp
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SyncFull Failed for UID {$uid}: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            
            return response()->json([
                'success' => false,
                'message' => 'Sync transaction failed. Changes rolled back.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }
}
