<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DailyNutritionSummary;
use App\Models\MealLogItem;
use App\Models\UserProfile;
use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Advanced analytics endpoints for individual user analysis.
 *
 * Provides:
 *  - Activity vs. Dietary correlation data (dual-axis chart)
 *  - Historical weight trend data
 *  - Itemized weekly progress reports
 */
class AnalyticsController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    //  Deliverable 2: Activity vs. Dietary Correlation
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /api/admin/analytics/correlation/{uid}
     *
     * Merges a user's daily nutrition summaries with their activity logs
     * by date, producing a time-aligned dataset for dual-axis visualization.
     *
     * Query params:
     *   ?days=30  (default: 30, max: 90)
     */
    public function correlation(string $uid, Request $request): JsonResponse
    {
        $days = min((int) $request->query('days', 30), 90);
        $todayEpochDay   = (int) floor(Carbon::now()->timestamp / 86400);
        $startEpochDay   = $todayEpochDay - $days + 1;
        $startEpochMs    = $startEpochDay * 86400 * 1000;

        // 1. Nutrition data keyed by epoch_day
        $nutrition = DailyNutritionSummary::where('uid', $uid)
            ->where('date_epoch_day', '>=', $startEpochDay)
            ->get()
            ->keyBy('date_epoch_day');

        // 2. Activity data grouped by epoch_day (summed per day)
        $activities = DB::table('activity_log_table')
            ->where('uid', $uid)
            ->where('timestamp', '>=', $startEpochMs)
            ->select(
                DB::raw('CAST(FLOOR(timestamp / 86400000) AS UNSIGNED) as date_epoch_day'),
                DB::raw('SUM(calories) as burned_calories'),
                DB::raw('SUM(COALESCE(steps, 0)) as total_steps'),
                DB::raw('SUM(COALESCE(distanceKm, 0)) as total_distance_km')
            )
            ->groupBy('date_epoch_day')
            ->get()
            ->keyBy('date_epoch_day');

        // 3. Merge by date
        $labels       = [];
        $intake       = [];
        $burned       = [];
        $steps        = [];
        $distance     = [];

        for ($day = $startEpochDay; $day <= $todayEpochDay; $day++) {
            $date = Carbon::createFromTimestamp($day * 86400);
            $labels[]   = $date->format('M d');

            $n = $nutrition->get($day);
            $a = $activities->get($day);

            $intake[]   = $n ? round($n->total_calories) : 0;
            $burned[]   = $a ? round($a->burned_calories) : 0;
            $steps[]    = $a ? (int) $a->total_steps : 0;
            $distance[] = $a ? round($a->total_distance_km, 2) : 0;
        }

        return response()->json([
            'uid'      => $uid,
            'days'     => $days,
            'labels'   => $labels,
            'intake'   => $intake,
            'burned'   => $burned,
            'steps'    => $steps,
            'distance' => $distance,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Deliverable 1: Historical Weight Trends
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /api/admin/analytics/weight-trend/{uid}
     *
     * Returns all historical weight entries for a user,
     * ordered chronologically for trend line visualization.
     *
     * Query params:
     *   ?days=90  (default: 90, max: 365)
     */
    public function weightTrend(string $uid, Request $request): JsonResponse
    {
        $days = min((int) $request->query('days', 90), 365);
        $sinceMs = (Carbon::now()->subDays($days)->timestamp) * 1000;

        $logs = WeightLog::where('uid', $uid)
            ->where('recorded_at', '>=', $sinceMs)
            ->orderBy('recorded_at', 'asc')
            ->get();

        $labels  = [];
        $weights = [];

        foreach ($logs as $log) {
            $date = Carbon::createFromTimestampMs($log->recorded_at);
            $labels[]  = $date->format('M d');
            $weights[] = round($log->weight, 1);
        }

        // Include current profile weight as latest point if no recent logs
        if (empty($weights)) {
            $profile = UserProfile::where('uid', $uid)->first();
            if ($profile && $profile->weight) {
                $labels[]  = Carbon::now()->format('M d');
                $weights[] = round($profile->weight, 1);
            }
        }

        return response()->json([
            'uid'     => $uid,
            'days'    => $days,
            'labels'  => $labels,
            'weights' => $weights,
            'count'   => count($weights),
        ]);
    }

    /**
     * POST /api/sync/weight-log
     *
     * Mobile sync endpoint for weight log entries.
     * Accepts a single entry or batch array.
     */
    public function syncWeightLog(Request $request): JsonResponse
    {
        $request->validate([
            'uid'         => 'required|string',
            'weight'      => 'required|numeric|min:20|max:500',
            'recorded_at' => 'required|integer',
        ]);

        $log = WeightLog::updateOrCreate(
            [
                'uid'         => $request->uid,
                'recorded_at' => $request->recorded_at,
            ],
            [
                'weight' => $request->weight,
            ]
        );

        return response()->json([
            'status'  => 'ok',
            'message' => 'Weight log synced.',
            'data'    => $log,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Deliverable 3: Itemized Weekly Progress Reports
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /api/admin/reports/weekly/{uid}
     *
     * Generates a structured analytical progress report for a user
     * over a specified date range.
     *
     * Query params:
     *   ?start_date=2026-04-22&end_date=2026-04-29
     *   (defaults to last 7 days if omitted)
     */
    public function weeklyReport(string $uid, Request $request): JsonResponse
    {
        // Parse date range
        $endDate   = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))
            : Carbon::now();
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))
            : $endDate->copy()->subDays(6);

        $startEpochDay = (int) floor($startDate->startOfDay()->timestamp / 86400);
        $endEpochDay   = (int) floor($endDate->endOfDay()->timestamp / 86400);
        $startMs       = $startEpochDay * 86400 * 1000;
        $endMs         = ($endEpochDay + 1) * 86400 * 1000;
        $numDays       = $endEpochDay - $startEpochDay + 1;

        // ── User Profile & TDEE ──
        // Use DB::table to bypass Eloquent casts (prevents DecryptException if APP_KEY changed)
        $profile = DB::table('user_profile')->where('uid', $uid)->first();
        
        if (!$profile) {
            return response()->json([
                'error' => 'User profile not found.',
                'uid' => $uid,
            ], 404);
        }

        $tdee = $this->calculateTDEE($profile);

        // ── Nutrition Aggregation ──
        $nutritionStats = DailyNutritionSummary::where('uid', $uid)
            ->whereBetween('date_epoch_day', [$startEpochDay, $endEpochDay])
            ->selectRaw('
                AVG(total_calories) as avg_calories,
                SUM(total_calories) as total_calories,
                AVG(total_protein) as avg_protein,
                AVG(total_carbs) as avg_carbs,
                AVG(total_fat) as avg_fat,
                COUNT(*) as days_logged
            ')
            ->first();

        $avgCalories    = round($nutritionStats->avg_calories ?? 0);
        $totalCalories  = round($nutritionStats->total_calories ?? 0);
        $daysLogged     = (int) ($nutritionStats->days_logged ?? 0);

        // Caloric deficit/surplus against TDEE
        $totalTdee      = $tdee * $numDays;
        $caloricBalance = $totalCalories - $totalTdee;

        // ── Activity Aggregation ──
        $activityStats = DB::table('activity_log_table')
            ->where('uid', $uid)
            ->whereBetween('timestamp', [$startMs, $endMs])
            ->selectRaw('
                AVG(COALESCE(steps, 0)) as avg_steps,
                SUM(COALESCE(steps, 0)) as total_steps,
                SUM(calories) as total_burned,
                AVG(calories) as avg_burned,
                SUM(COALESCE(distanceKm, 0)) as total_distance,
                COUNT(*) as activity_count
            ')
            ->first();

        $avgSteps      = round($activityStats->avg_steps ?? 0);
        $totalSteps    = (int) ($activityStats->total_steps ?? 0);
        $totalBurned   = round($activityStats->total_burned ?? 0);
        $activityCount = (int) ($activityStats->activity_count ?? 0);

        // ── Top 3 Most Consumed Foods ──
        $topFoods = MealLogItem::join('meal_log_table', 'meal_log_table.meal_log_id', '=', 'meal_log_item_table.meal_log_id')
            ->where('meal_log_table.uid', $uid)
            ->whereBetween('meal_log_table.timestamp', [$startMs, $endMs])
            ->select(
                'meal_log_item_table.dish_name',
                DB::raw('COUNT(*) as times_consumed'),
                DB::raw('ROUND(AVG(meal_log_item_table.calories), 0) as avg_calories_per_serving'),
                DB::raw('ROUND(SUM(meal_log_item_table.weight_grams), 0) as total_grams')
            )
            ->groupBy('meal_log_item_table.dish_name')
            ->orderByDesc('times_consumed')
            ->limit(3)
            ->get();

        // ── Daily Breakdown (for charts) ──
        $dailyBreakdown = [];
        for ($day = $startEpochDay; $day <= $endEpochDay; $day++) {
            $date     = Carbon::createFromTimestamp($day * 86400)->format('Y-m-d');
            $dayMs    = $day * 86400 * 1000;
            $dayEndMs = ($day + 1) * 86400 * 1000;

            $dayNutrition = DailyNutritionSummary::where('uid', $uid)
                ->where('date_epoch_day', $day)
                ->first();

            $dayActivity = DB::table('activity_log_table')
                ->where('uid', $uid)
                ->whereBetween('timestamp', [$dayMs, $dayEndMs])
                ->selectRaw('SUM(COALESCE(steps, 0)) as steps, SUM(calories) as burned')
                ->first();

            $dailyBreakdown[] = [
                'date'     => $date,
                'calories' => $dayNutrition ? round($dayNutrition->total_calories) : 0,
                'steps'    => $dayActivity ? (int) $dayActivity->steps : 0,
                'burned'   => $dayActivity ? round($dayActivity->burned) : 0,
            ];
        }

        return response()->json([
            'uid'       => $uid,
            'user_name' => 'Mobile User', // Fallback since actual name might be encrypted raw bytes
            'period'    => [
                'start'    => $startDate->format('Y-m-d'),
                'end'      => $endDate->format('Y-m-d'),
                'num_days' => $numDays,
            ],
            'tdee'      => $tdee,
            'nutrition' => [
                'avg_daily_calories' => $avgCalories,
                'total_calories'     => $totalCalories,
                'avg_protein'        => round($nutritionStats->avg_protein ?? 0),
                'avg_carbs'          => round($nutritionStats->avg_carbs ?? 0),
                'avg_fat'            => round($nutritionStats->avg_fat ?? 0),
                'days_logged'        => $daysLogged,
                'compliance_pct'     => $numDays > 0 ? round(($daysLogged / $numDays) * 100) : 0,
            ],
            'caloric_balance' => [
                'total_intake'  => $totalCalories,
                'total_tdee'    => $totalTdee,
                'balance'       => round($caloricBalance),
                'status'        => $caloricBalance > 0 ? 'Surplus' : ($caloricBalance < 0 ? 'Deficit' : 'Balanced'),
                'daily_avg_gap' => $numDays > 0 ? round($caloricBalance / $numDays) : 0,
            ],
            'activity' => [
                'avg_daily_steps'    => $avgSteps,
                'total_steps'        => $totalSteps,
                'total_burned'       => $totalBurned,
                'total_distance_km'  => round($activityStats->total_distance ?? 0, 2),
                'activity_count'     => $activityCount,
            ],
            'top_foods'       => $topFoods,
            'daily_breakdown' => $dailyBreakdown,
        ]);
    }

    /**
     * Calculate TDEE using Mifflin-St Jeor equation.
     */
    private function calculateTDEE($profile): float
    {
        if (!$profile->weight || !$profile->height || !$profile->age || !$profile->sex) {
            return 0;
        }

        $bmr = (10 * $profile->weight) + (6.25 * $profile->height) - (5 * $profile->age);
        $bmr = $profile->sex === 'Male' ? $bmr + 5 : $bmr - 161;

        $multiplier = match ($profile->activityLevel) {
            'lightly_active' => 1.375,
            'active'         => 1.55,
            'very_active'    => 1.725,
            default          => 1.2,
        };

        return round($bmr * $multiplier);
    }
}
