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
     *  - activeParticipants : unique users with a DailyNutritionSummary or MealLog in the last 7 days
     *  - mealsThisWeek      : total MealLog records created in the last 7 days
     *  - adherence          : average (consumed / target) * 100 across active users this week
     */
    public function getStats(): JsonResponse
    {
        // ── Time boundaries ──
        $sevenDaysAgo  = Carbon::now()->subDays(7);
        $todayEpochDay = (int) floor(Carbon::now()->timestamp / 86400);
        $weekAgoEpochDay = (int) floor($sevenDaysAgo->timestamp / 86400);

        // ── 1. Active Participants ──
        // Unique UIDs that created a DailyNutritionSummary OR a MealLog in the last 7 days.
        $nutritionUids = DailyNutritionSummary::where('date_epoch_day', '>=', $weekAgoEpochDay)
            ->pluck('uid');

        $mealLogUids = MealLog::where('created_at', '>=', $sevenDaysAgo)
            ->pluck('uid');

        $activeUids = $nutritionUids->merge($mealLogUids)->unique();
        $activeParticipants = $activeUids->count();

        // ── 2. Meals This Week ──
        $mealsThisWeek = MealLog::where('created_at', '>=', $sevenDaysAgo)->count();

        // ── 3. Average Caloric Target Adherence ──
        // For each active user, compute: (sum consumed calories) / (target calories * days) * 100
        // Target calories = TDEE derived from user profile (Mifflin-St Jeor).
        $adherence = 0;
        $usersWithAdherence = 0;

        if ($activeUids->isNotEmpty()) {
            // Get profiles for active users
            $profiles = UserProfile::whereIn('uid', $activeUids)->get()->keyBy('uid');

            // Get weekly nutrition summaries for active users
            $weeklySummaries = DailyNutritionSummary::whereIn('uid', $activeUids)
                ->where('date_epoch_day', '>=', $weekAgoEpochDay)
                ->get()
                ->groupBy('uid');

            foreach ($activeUids as $uid) {
                $profile = $profiles->get($uid);
                if (!$profile) continue;

                $tdee = $this->calculateTDEE($profile);
                if ($tdee <= 0) continue;

                $userSummaries = $weeklySummaries->get($uid);
                if (!$userSummaries || $userSummaries->isEmpty()) continue;

                $totalConsumed = $userSummaries->sum('total_calories');
                $daysTracked   = $userSummaries->count();
                $targetCalories = $tdee * $daysTracked;

                if ($targetCalories > 0) {
                    $userAdherence = ($totalConsumed / $targetCalories) * 100;
                    $adherence += $userAdherence;
                    $usersWithAdherence++;
                }
            }

            $adherence = $usersWithAdherence > 0
                ? round($adherence / $usersWithAdherence, 1)
                : 0;
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

        // Active profiles for TDEE calculation
        $profiles = UserProfile::where('is_active', true)->get();
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
        $profiles = UserProfile::all();

        $active   = $profiles->filter(fn($p) => ($p->streak ?? 0) > 0);
        $inactive = $profiles->filter(fn($p) => ($p->streak ?? 0) === 0);

        $totalStreak = $profiles->sum('streak');
        $avgConsistency = $profiles->count() > 0
            ? round($totalStreak / $profiles->count())
            : 0;

        return response()->json([
            'activeCount'    => $active->count(),
            'inactiveCount'  => $inactive->count(),
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
