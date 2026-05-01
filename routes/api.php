<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\FoodItemController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\MealLogController;
use App\Http\Controllers\Api\DailyNutritionSummaryController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\SystemLogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AnalyticsController;

/*
|--------------------------------------------------------------------------
| CalorieKo API Routes
|--------------------------------------------------------------------------
|
| Mobile sync endpoints (require Firebase auth):
|   POST /api/sync/full              ← Master atomic sync (all data in one transaction)
|   POST /api/sync/profile
|   POST /api/sync/food
|   POST /api/sync/activity-log
|   POST /api/sync/activity-log/batch
|   POST /api/sync/meal-log
|   POST /api/sync/nutrition-summary
|   POST /api/sync/weight-log        ← NEW: Historical weight tracking
|
| Admin auth endpoints (public):
|   POST /api/admin/login
|
| Admin auth endpoints (Sanctum-protected):
|   POST /api/admin/verify
|   POST /api/admin/logout
|
| Admin endpoints (require sanctum + admin.auth):
|   GET  /api/admin/dashboard/stats
|   GET  /api/admin/analytics/nutrition-trends
|   GET  /api/admin/analytics/top-dishes
|   GET  /api/admin/analytics/user-consistency
|   GET  /api/admin/analytics/correlation/{uid}     ← NEW
|   GET  /api/admin/analytics/weight-trend/{uid}    ← NEW
|   GET  /api/admin/reports/weekly/{uid}             ← NEW
|   ...CRUD endpoints for profiles, foods, activity-logs, etc.
|
*/

// ── Mobile Sync Endpoints (Firebase Auth required) ──
Route::prefix('sync')
    ->middleware('firebase.auth')
    ->group(function () {
        // Master Atomic Sync Route
        Route::post('/full',              [\App\Http\Controllers\Api\MobileSyncController::class, 'syncFull']);
        
        Route::post('/profile',           [UserProfileController::class, 'sync']);
        Route::post('/food',              [FoodItemController::class, 'sync']);
        Route::post('/activity-log',      [ActivityLogController::class, 'sync']);
        Route::post('/activity-log/batch',[ActivityLogController::class, 'syncBatch']);
        Route::post('/meal-log',          [MealLogController::class, 'sync']);
        Route::post('/nutrition-summary', [DailyNutritionSummaryController::class, 'sync']);
        Route::post('/weight-log',        [AnalyticsController::class, 'syncWeightLog']);
    });

// ── Admin Auth Endpoints (public — no middleware) ──
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// ── Admin Auth Endpoints (Sanctum-protected, no role check) ──
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/verify',  [AdminAuthController::class, 'verify']);
    Route::post('/admin/logout',  [AdminAuthController::class, 'logout']);
});

// ── Admin Endpoints (secured with Sanctum + admin.auth middleware) ──
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin.auth'])
    ->group(function () {

    // ── Dashboard KPI Stats ──
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    // ── Analytics (chart aggregation endpoints) ──
    Route::get('/analytics/nutrition-trends',  [DashboardController::class, 'nutritionTrends']);
    Route::get('/analytics/step-trends',       [DashboardController::class, 'stepTrends']);
    Route::get('/analytics/top-dishes',        [DashboardController::class, 'topDishes']);
    Route::get('/analytics/user-consistency',  [DashboardController::class, 'userConsistency']);

    // ── NEW: Individual User Analytics ──
    Route::get('/analytics/correlation/{uid}',  [AnalyticsController::class, 'correlation']);
    Route::get('/analytics/weight-trend/{uid}', [AnalyticsController::class, 'weightTrend']);

    // ── NEW: Weekly Progress Reports ──
    Route::get('/reports/weekly/{uid}',         [AnalyticsController::class, 'weeklyReport']);

    // ── User Profiles ──
    Route::get('/profiles',                     [UserProfileController::class, 'index']);
    Route::get('/profiles/{uid}',               [UserProfileController::class, 'show']);
    Route::put('/profiles/{uid}/deactivate',    [UserProfileController::class, 'deactivate']);
    Route::post('/profiles/{uid}/reset-password',[UserProfileController::class, 'resetPassword']);
    Route::delete('/profiles/{uid}',            [UserProfileController::class, 'destroy']);

    // ── Food Items ──
    Route::get('/foods',              [FoodItemController::class, 'index']);
    Route::get('/foods/{id}',         [FoodItemController::class, 'show']);
    Route::post('/foods',             [FoodItemController::class, 'store']);
    Route::put('/foods/{id}',         [FoodItemController::class, 'update']);
    Route::delete('/foods/{id}',      [FoodItemController::class, 'destroy']);
    Route::post('/foods/bulk-import', [FoodItemController::class, 'bulkImport']);

    // ── Activity Logs ──
    Route::get('/activity-logs',      [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show']);

    // ── Meal Logs (with items) ──
    Route::get('/meal-logs',          [MealLogController::class, 'index']);
    Route::get('/meal-logs/{id}',     [MealLogController::class, 'show']);

    // ── Daily Nutrition Summaries ──
    Route::get('/nutrition-summaries',      [DailyNutritionSummaryController::class, 'index']);
    Route::get('/nutrition-summaries/{id}', [DailyNutritionSummaryController::class, 'show']);

    // ── System Logs ──
    Route::get('/system-logs',              [SystemLogController::class, 'index']);
    // ── Admin & Moderator Management (Super Admin Only) ──
    Route::get('/moderators', [App\Http\Controllers\Api\AdminManagementController::class, 'index']);
    Route::post('/moderators', [App\Http\Controllers\Api\AdminManagementController::class, 'store']);
    Route::put('/moderators/{id}/toggle', [App\Http\Controllers\Api\AdminManagementController::class, 'toggle']);
    Route::delete('/moderators/{id}', [App\Http\Controllers\Api\AdminManagementController::class, 'destroy']);
    
    // ── Self-Service Admin Actions ──
    Route::put('/password', [App\Http\Controllers\Api\AdminAuthController::class, 'updatePassword']);
});

// Health check with encryption verification
Route::get('/health', function () {
    $cipher = config('app.cipher');
    $key = config('app.key');
    $isConfigured = !empty($key) && !empty($cipher);

    return response()->json([
        'status' => 'ok',
        'app' => 'CalorieKo API',
        'encryption' => [
            'active' => $isConfigured,
            'cipher' => $cipher,
            'at_rest' => true // Since we have enabled model casts
        ]
    ]);
});
