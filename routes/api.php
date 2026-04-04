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
|
| Admin auth endpoints (public):
|   POST /api/admin/login
|   POST /api/admin/verify
|
| Admin endpoints (require admin.auth):
|   GET  /api/admin/dashboard/stats
|   GET  /api/admin/analytics/nutrition-trends
|   GET  /api/admin/analytics/top-dishes
|   GET  /api/admin/analytics/user-consistency
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
    });

// ── Admin Auth Endpoints (public — no middleware) ──
Route::post('/admin/login',  [AdminAuthController::class, 'login']);
Route::post('/admin/verify', [AdminAuthController::class, 'verify']);

// ── Admin Endpoints (secured with admin.auth middleware) ──
Route::prefix('admin')
    ->middleware('admin.auth')
    ->group(function () {

    // ── Dashboard KPI Stats ──
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    // ── Analytics (chart aggregation endpoints) ──
    Route::get('/analytics/nutrition-trends',  [DashboardController::class, 'nutritionTrends']);
    Route::get('/analytics/top-dishes',        [DashboardController::class, 'topDishes']);
    Route::get('/analytics/user-consistency',  [DashboardController::class, 'userConsistency']);

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
