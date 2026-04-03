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

        // 1. Active Exercisers — unique users who logged a workout in the actual past 7 days
        // Convert 7 days ago to epoch milliseconds to match the mobile app's timestamp format
        $sevenDaysAgoMillis = $sevenDaysAgo->timestamp * 1000;

        $activeParticipants = \App\Models\ActivityLog::where('type', 'workout')
            ->where('timestamp', '>=', $sevenDaysAgoMillis)
            ->distinct('uid')
            ->count('uid');

        // 2. Meals This Week
        $mealsThisWeek = MealLog::where('created_at', '>=', $sevenDaysAgo)->count();

        // 3. Adherence
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

        // Use the unified virtual attribute 'is_engaged' (is_active AND streak > 0)
        $engaged = $profiles->filter(fn($p) => $p->is_engaged);
        $dormant = $profiles->filter(fn($p) => !$p->is_engaged);

        $totalStreak = $profiles->sum('streak');
        $avgConsistency = $profiles->count() > 0
            ? round($totalStreak / $profiles->count())
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
