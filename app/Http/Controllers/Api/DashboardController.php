<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyNutritionSummary;
use App\Models\MealLog;
use App\Models\MealLogItem;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard/stats
     *
     * Returns aggregated KPI data for the admin overview tiles:
     *  - activeParticipants : users with an active tracking streak (streak > 0)
     *  - mealsThisWeek      : total MealLog records created in the last 7 days
     *  - adherence          : avg daily calories / 2000 kcal baseline × 100, capped at 100
     */
    public function getStats(): JsonResponse
    {
        $sevenDaysAgo    = Carbon::now()->subDays(7);
        $weekAgoEpochDay = (int) floor($sevenDaysAgo->timestamp / 86400);

        // 1. Engaged Participants — users who have logged ANY meal or activity in the last 7 days
        //    (streak is not set by the mobile app, so we check actual data instead)
        $sevenDaysAgoMs  = (int) ($sevenDaysAgo->timestamp * 1000); // epoch millis for mobile timestamp

        $mealUids = MealLog::where('timestamp', '>=', $sevenDaysAgoMs)
            ->distinct()->pluck('uid');
        $activityUids = DB::table('activity_log_table')
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->distinct()->pluck('uid');
        $activeParticipants = $mealUids->merge($activityUids)->unique()->count();

        // 2. Meals This Week — count by mobile epoch-millis timestamp (not Laravel created_at)
        $mealsThisWeek = MealLog::where('timestamp', '>=', $sevenDaysAgoMs)->count();

        // 3. Adherence — avg(total_calories) from the last 7 days vs 2000 kcal baseline
        $avgCalories = DailyNutritionSummary::where('date_epoch_day', '>=', $weekAgoEpochDay)
            ->avg('total_calories');

        $adherence = 0;
        if ($avgCalories && $avgCalories > 0) {
            $adherence = min(100, (int) round(($avgCalories / 2000) * 100));
        }

        return response()->json([
            'activeParticipants' => $activeParticipants,
            'mealsThisWeek'     => $mealsThisWeek,
            'adherence'         => $adherence,
        ]);
    }

    /**
     * GET /api/admin/analytics/nutrition-trends
     *
     * Returns daily average calorie intake + average TDEE for the last 7 days.
     */
    public function nutritionTrends(): JsonResponse
    {
        $todayEpochDay   = (int) floor(Carbon::now()->timestamp / 86400);
        $weekAgoEpochDay = $todayEpochDay - 6; // includes today = 7 days

        // Active profiles for TDEE calculation (exclude suspended users)
        $profiles = UserProfile::active()->get();
        $activeUsersCount = $profiles->count();

        $totalTDEE = $profiles->sum(fn($p) => $this->calculateTDEE($p));
        $averageTDEE = $activeUsersCount > 0 ? round($totalTDEE / $activeUsersCount) : 0;

        // Daily summaries for the week
        $summaries = DailyNutritionSummary::where('date_epoch_day', '>=', $weekAgoEpochDay)
            ->select('date_epoch_day', DB::raw('SUM(total_calories) as day_total'))
            ->groupBy('date_epoch_day')
            ->pluck('day_total', 'date_epoch_day');

        $labels    = [];
        $intake    = [];
        $tdeeData  = [];

        for ($day = $weekAgoEpochDay; $day <= $todayEpochDay; $day++) {
            $date = Carbon::createFromTimestamp($day * 86400);
            $labels[] = $date->format('D');

            $dayTotal = $summaries->get($day, 0);
            $avgDayCalories = $activeUsersCount > 0 ? round($dayTotal / $activeUsersCount) : 0;

            $intake[]   = $avgDayCalories;
            $tdeeData[] = $averageTDEE;
        }

        return response()->json([
            'labels'       => $labels,
            'intake'       => $intake,
            'tdee'         => $tdeeData,
            'averageTDEE'  => $averageTDEE,
            'averageIntake' => count($intake) > 0 ? round(array_sum($intake) / count($intake)) : 0,
        ]);
    }

    /**
     * GET /api/admin/analytics/step-trends
     *
     * Returns daily average steps for the last 7 days.
     */
    public function stepTrends(): JsonResponse
    {
        $todayEpochDay   = (int) floor(Carbon::now()->timestamp / 86400);
        $weekAgoEpochDay = $todayEpochDay - 6;

        $sevenDaysAgoMs = $weekAgoEpochDay * 86400 * 1000;
        
        $profiles = UserProfile::active()->get();
        $activeUsersCount = $profiles->count();

        // Group activity logs by epoch day and sum steps
        $activities = DB::table('activity_log_table')
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->whereNotNull('steps')
            ->select(DB::raw('CAST(FLOOR(timestamp / 86400000) AS UNSIGNED) as date_epoch_day'), DB::raw('SUM(steps) as day_total'))
            ->groupBy('date_epoch_day')
            ->pluck('day_total', 'date_epoch_day');

        $labels = [];
        $steps  = [];

        for ($day = $weekAgoEpochDay; $day <= $todayEpochDay; $day++) {
            $date = Carbon::createFromTimestamp($day * 86400);
            $labels[] = $date->format('D');

            $dayTotal = $activities->get($day, 0);
            $avgDaySteps = $activeUsersCount > 0 ? round($dayTotal / $activeUsersCount) : 0;

            $steps[] = $avgDaySteps;
        }

        return response()->json([
            'labels'       => $labels,
            'steps'        => $steps,
            'averageSteps' => count($steps) > 0 ? round(array_sum($steps) / count($steps)) : 0,
        ]);
    }

    /**
     * GET /api/admin/analytics/top-dishes
     *
     * Returns the top 5 most-logged dish names across all MealLogItems.
     */
    public function topDishes(): JsonResponse
    {
        $topDishes = MealLogItem::select('dish_name', DB::raw('COUNT(*) as times_logged'))
            ->groupBy('dish_name')
            ->orderByDesc('times_logged')
            ->limit(5)
            ->get();

        return response()->json([
            'labels' => $topDishes->pluck('dish_name'),
            'data'   => $topDishes->pluck('times_logged'),
        ]);
    }

    /**
     * GET /api/admin/analytics/user-consistency
     *
     * Returns active vs inactive user counts and average consistency (streak).
     */
    public function userConsistency(): JsonResponse
    {
        // Only evaluate users whose accounts aren't suspended (is_active = true)
        $profiles = UserProfile::active()->get();

        // Use actual recent logging activity (last 7 days) instead of streak
        $sevenDaysAgoMs = (int) (Carbon::now()->subDays(7)->timestamp * 1000);

        $recentUids = DB::table('meal_log_table')
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->select('uid')
            ->union(
                DB::table('activity_log_table')
                    ->where('timestamp', '>=', $sevenDaysAgoMs)
                    ->select('uid')
            )->pluck('uid')->unique();

        $engaged = $profiles->filter(fn($p) => $recentUids->contains($p->uid));
        $dormant = $profiles->filter(fn($p) => !$recentUids->contains($p->uid));

        // Avg consistency = average number of distinct days (out of 7) each active user has logged
        // ANY data (food OR activity). Uses a UNION query to merge both tables.
        $weekAgoEpochDay = (int) floor(Carbon::now()->subDays(7)->timestamp / 86400);

        // Nutrition dates (epoch_day)
        $nutritionQuery = DB::table('daily_nutrition_summary_table')
            ->where('date_epoch_day', '>=', $weekAgoEpochDay)
            ->select('uid', DB::raw('date_epoch_day as active_day'));

        // Activity dates (epoch_millis → epoch_day via integer division)
        $activityQuery = DB::table('activity_log_table')
            ->where('timestamp', '>=', $sevenDaysAgoMs)
            ->select('uid', DB::raw('CAST(FLOOR(timestamp / 86400000) AS UNSIGNED) as active_day'));

        // Union both, then group by uid and count distinct days
        $daysPerUser = DB::query()
            ->fromSub($nutritionQuery->union($activityQuery), 'merged')
            ->select('uid', DB::raw('COUNT(DISTINCT active_day) as days_logged'))
            ->groupBy('uid')
            ->pluck('days_logged');

        $avgConsistency = $daysPerUser->count() > 0
            ? round($daysPerUser->avg())
            : 0;

        return response()->json([
            'activeCount'    => $engaged->count(),
            'inactiveCount'  => $dormant->count(),
            'avgConsistency' => $avgConsistency,
        ]);
    }

    /**
     * Calculate TDEE using Mifflin-St Jeor equation.
     */
    private function calculateTDEE(UserProfile $profile): float
    {
        if (!$profile->weight || !$profile->height || !$profile->age || !$profile->sex) {
            return 0;
        }

        // Mifflin-St Jeor BMR
        $bmr = (10 * $profile->weight) + (6.25 * $profile->height) - (5 * $profile->age);
        $bmr = $profile->sex === 'Male' ? $bmr + 5 : $bmr - 161;

        // Activity multiplier
        $multiplier = match ($profile->activityLevel) {
            'lightly_active' => 1.375,
            'active'         => 1.55,
            'very_active'    => 1.725,
            default          => 1.2,
        };

        return round($bmr * $multiplier);
    }
}
