<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\FoodItem;
use App\Models\MealLog;
use App\Models\MealLogItem;
use App\Models\DailyNutritionSummary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ────────────────────────────────────────────────────────
        // 1. Admin Login User
        // ────────────────────────────────────────────────────────
        User::factory()->create([
            'name' => 'Admin Tester',
            'email' => 'admin@calorieko.test',
        ]);

        // ────────────────────────────────────────────────────────
        // 2. Filipino Food Items
        // ────────────────────────────────────────────────────────
        $foods = [
            ['name_en' => 'Adobo (Chicken)',    'name_ph' => 'Adobong Manok',       'category' => 'Main Dish',   'calories_per_100g' => 180, 'protein_per_100g' => 20.0, 'carbs_per_100g' => 3.0,  'fat_per_100g' => 10.0, 'fiber_per_100g' => 0.2, 'sugar_per_100g' => 1.0, 'saturated_fat_per_100g' => 3.0, 'sodium_per_100g' => 600],
            ['name_en' => 'Sinigang na Baboy',  'name_ph' => 'Sinigang na Baboy',   'category' => 'Soup',        'calories_per_100g' => 85,  'protein_per_100g' => 8.0,  'carbs_per_100g' => 5.0,  'fat_per_100g' => 4.0,  'fiber_per_100g' => 1.5, 'sugar_per_100g' => 2.0, 'saturated_fat_per_100g' => 1.5, 'sodium_per_100g' => 450],
            ['name_en' => 'Fried Rice',         'name_ph' => 'Sinangag',             'category' => 'Rice',        'calories_per_100g' => 165, 'protein_per_100g' => 3.5,  'carbs_per_100g' => 30.0, 'fat_per_100g' => 3.5,  'fiber_per_100g' => 0.5, 'sugar_per_100g' => 0.3, 'saturated_fat_per_100g' => 0.8, 'sodium_per_100g' => 280],
            ['name_en' => 'Lumpia (Fried)',      'name_ph' => 'Lumpia Shanghai',      'category' => 'Appetizer',   'calories_per_100g' => 220, 'protein_per_100g' => 10.0, 'carbs_per_100g' => 18.0, 'fat_per_100g' => 12.0, 'fiber_per_100g' => 1.0, 'sugar_per_100g' => 2.0, 'saturated_fat_per_100g' => 3.5, 'sodium_per_100g' => 420],
            ['name_en' => 'Pancit Canton',       'name_ph' => 'Pancit Canton',        'category' => 'Noodles',     'calories_per_100g' => 150, 'protein_per_100g' => 5.0,  'carbs_per_100g' => 22.0, 'fat_per_100g' => 5.0,  'fiber_per_100g' => 1.0, 'sugar_per_100g' => 1.5, 'saturated_fat_per_100g' => 1.2, 'sodium_per_100g' => 520],
            ['name_en' => 'Kare-Kare',          'name_ph' => 'Kare-Kare',            'category' => 'Main Dish',   'calories_per_100g' => 160, 'protein_per_100g' => 14.0, 'carbs_per_100g' => 8.0,  'fat_per_100g' => 9.0,  'fiber_per_100g' => 2.0, 'sugar_per_100g' => 3.0, 'saturated_fat_per_100g' => 2.5, 'sodium_per_100g' => 350],
            ['name_en' => 'Lechon Kawali',       'name_ph' => 'Lechon Kawali',        'category' => 'Main Dish',   'calories_per_100g' => 280, 'protein_per_100g' => 18.0, 'carbs_per_100g' => 0.5,  'fat_per_100g' => 22.0, 'fiber_per_100g' => 0.0, 'sugar_per_100g' => 0.0, 'saturated_fat_per_100g' => 8.0, 'sodium_per_100g' => 380],
            ['name_en' => 'Tinola (Chicken)',    'name_ph' => 'Tinolang Manok',       'category' => 'Soup',        'calories_per_100g' => 75,  'protein_per_100g' => 10.0, 'carbs_per_100g' => 4.0,  'fat_per_100g' => 2.5,  'fiber_per_100g' => 1.0, 'sugar_per_100g' => 1.5, 'saturated_fat_per_100g' => 0.8, 'sodium_per_100g' => 400],
            ['name_en' => 'Steamed Rice',        'name_ph' => 'Kanin',                'category' => 'Rice',        'calories_per_100g' => 130, 'protein_per_100g' => 2.7,  'carbs_per_100g' => 28.0, 'fat_per_100g' => 0.3,  'fiber_per_100g' => 0.4, 'sugar_per_100g' => 0.1, 'saturated_fat_per_100g' => 0.1, 'sodium_per_100g' => 1],
            ['name_en' => 'Tapsilog',            'name_ph' => 'Tapsilog',             'category' => 'Breakfast',   'calories_per_100g' => 210, 'protein_per_100g' => 16.0, 'carbs_per_100g' => 20.0, 'fat_per_100g' => 8.0,  'fiber_per_100g' => 0.5, 'sugar_per_100g' => 2.0, 'saturated_fat_per_100g' => 3.0, 'sodium_per_100g' => 550],
            ['name_en' => 'Bangus (Milkfish)',   'name_ph' => 'Bangus',               'category' => 'Seafood',     'calories_per_100g' => 148, 'protein_per_100g' => 20.0, 'carbs_per_100g' => 0.0,  'fat_per_100g' => 7.0,  'fiber_per_100g' => 0.0, 'sugar_per_100g' => 0.0, 'saturated_fat_per_100g' => 2.2, 'sodium_per_100g' => 72],
            ['name_en' => 'Pinakbet',            'name_ph' => 'Pinakbet',             'category' => 'Vegetable',   'calories_per_100g' => 55,  'protein_per_100g' => 3.0,  'carbs_per_100g' => 7.0,  'fat_per_100g' => 2.0,  'fiber_per_100g' => 3.0, 'sugar_per_100g' => 3.0, 'saturated_fat_per_100g' => 0.5, 'sodium_per_100g' => 480],
            ['name_en' => 'Leche Flan',          'name_ph' => 'Leche Flan',           'category' => 'Dessert',     'calories_per_100g' => 250, 'protein_per_100g' => 6.0,  'carbs_per_100g' => 35.0, 'fat_per_100g' => 10.0, 'fiber_per_100g' => 0.0, 'sugar_per_100g' => 30.0,'saturated_fat_per_100g' => 4.5, 'sodium_per_100g' => 75],
            ['name_en' => 'Tokwa\'t Baboy',      'name_ph' => 'Tokwa\'t Baboy',       'category' => 'Appetizer',   'calories_per_100g' => 170, 'protein_per_100g' => 14.0, 'carbs_per_100g' => 5.0,  'fat_per_100g' => 11.0, 'fiber_per_100g' => 0.5, 'sugar_per_100g' => 2.0, 'saturated_fat_per_100g' => 3.5, 'sodium_per_100g' => 680],
            ['name_en' => 'Banana (Saba)',       'name_ph' => 'Saging na Saba',       'category' => 'Fruit',       'calories_per_100g' => 120, 'protein_per_100g' => 1.3,  'carbs_per_100g' => 30.0, 'fat_per_100g' => 0.4,  'fiber_per_100g' => 2.0, 'sugar_per_100g' => 15.0,'saturated_fat_per_100g' => 0.1, 'sodium_per_100g' => 1],
        ];

        $foodModels = [];
        foreach ($foods as $food) {
            $foodModels[] = FoodItem::create($food);
        }

        // ────────────────────────────────────────────────────────
        // 3. Mock Mobile App Participants
        // ────────────────────────────────────────────────────────
        $sexes = ['Male', 'Female'];
        $activityLevels = ['lightly_active', 'active', 'very_active'];
        $goals = ['Maintain weight', 'Lose weight', 'Gain weight'];

        $participants = [];
        for ($i = 1; $i <= 15; $i++) {
            $participants[] = UserProfile::create([
                'uid'           => Str::random(28),
                'email'         => "participant{$i}@example.com",
                'name'          => "Participant {$i}",
                'is_active'     => $i <= 12,  // 12 active, 3 inactive
                'age'           => rand(18, 60),
                'weight'        => rand(50, 100),
                'height'        => rand(150, 190),
                'goal'          => $goals[array_rand($goals)],
                'sex'           => $sexes[array_rand($sexes)],
                'activityLevel' => $activityLevels[array_rand($activityLevels)],
                'streak'        => $i <= 12 ? rand(1, 14) : 0,
                'level'         => rand(1, 5),
            ]);
        }

        // ────────────────────────────────────────────────────────
        // 4. Meal Logs + Items (last 7 days for active participants)
        // ────────────────────────────────────────────────────────
        $mealTypes = ['Breakfast', 'Lunch', 'Dinner', 'Snacks'];
        $now = Carbon::now();

        // Only create tracking data for active participants (first 12)
        $activeParticipants = array_slice($participants, 0, 12);

        foreach ($activeParticipants as $participant) {
            // Each active user logs meals on 4-7 of the last 7 days
            $daysActive = rand(4, 7);
            $daysToLog = (array) array_rand(range(0, 6), $daysActive);

            foreach ($daysToLog as $dayOffset) {
                $logDate = $now->copy()->subDays($dayOffset);

                // 2-4 meals per day
                $mealsToday = rand(2, 4);
                $usedMealTypes = (array) array_rand(array_flip($mealTypes), $mealsToday);

                $dayTotalCalories = 0;
                $dayTotalProtein  = 0;
                $dayTotalCarbs    = 0;
                $dayTotalFat      = 0;
                $dayTotalFiber    = 0;
                $dayTotalSugar    = 0;
                $dayTotalSatFat   = 0;
                $dayTotalSodium   = 0;

                $breakfastCal = 0;
                $lunchCal     = 0;
                $dinnerCal    = 0;
                $snacksCal    = 0;

                foreach ($usedMealTypes as $mealType) {
                    // Set timestamp to a reasonable hour for each meal type
                    $hour = match ($mealType) {
                        'Breakfast' => rand(6, 9),
                        'Lunch'     => rand(11, 13),
                        'Dinner'    => rand(17, 20),
                        'Snacks'    => rand(14, 16),
                    };
                    $logTimestamp = $logDate->copy()->setHour($hour)->setMinute(rand(0, 59));

                    $mealLog = MealLog::create([
                        'uid'       => $participant->uid,
                        'meal_type' => $mealType,
                        'timestamp' => $logTimestamp->timestamp * 1000,  // epoch millis
                        'notes'     => null,
                        'created_at' => $logTimestamp,
                        'updated_at' => $logTimestamp,
                    ]);

                    // 1-3 food items per meal
                    $itemCount = rand(1, 3);
                    $selectedFoods = array_rand($foodModels, min($itemCount, count($foodModels)));
                    if (!is_array($selectedFoods)) $selectedFoods = [$selectedFoods];

                    foreach ($selectedFoods as $foodIdx) {
                        $food   = $foodModels[$foodIdx];
                        $weight = rand(80, 300); // grams
                        $ratio  = $weight / 100;

                        $cal     = round($food->calories_per_100g * $ratio, 1);
                        $protein = round($food->protein_per_100g * $ratio, 1);
                        $carbs   = round($food->carbs_per_100g * $ratio, 1);
                        $fat     = round($food->fat_per_100g * $ratio, 1);
                        $fiber   = round($food->fiber_per_100g * $ratio, 1);
                        $sugar   = round($food->sugar_per_100g * $ratio, 1);
                        $satFat  = round($food->saturated_fat_per_100g * $ratio, 1);
                        $sodium  = round($food->sodium_per_100g * $ratio, 1);

                        MealLogItem::create([
                            'meal_log_id'  => $mealLog->meal_log_id,
                            'food_id'      => $food->food_id,
                            'dish_name'    => $food->name_en,
                            'weight_grams' => $weight,
                            'calories'     => $cal,
                            'protein'      => $protein,
                            'carbs'        => $carbs,
                            'fat'          => $fat,
                            'fiber'        => $fiber,
                            'sugar'        => $sugar,
                            'saturated_fat' => $satFat,
                            'sodium'       => $sodium,
                        ]);

                        $dayTotalCalories += $cal;
                        $dayTotalProtein  += $protein;
                        $dayTotalCarbs    += $carbs;
                        $dayTotalFat      += $fat;
                        $dayTotalFiber    += $fiber;
                        $dayTotalSugar    += $sugar;
                        $dayTotalSatFat   += $satFat;
                        $dayTotalSodium   += $sodium;

                        match ($mealType) {
                            'Breakfast' => $breakfastCal += $cal,
                            'Lunch'     => $lunchCal += $cal,
                            'Dinner'    => $dinnerCal += $cal,
                            'Snacks'    => $snacksCal += $cal,
                        };
                    }
                }

                // ── Create Daily Nutrition Summary for this user + day ──
                $epochDay = (int) floor($logDate->startOfDay()->timestamp / 86400);

                DailyNutritionSummary::updateOrCreate(
                    ['uid' => $participant->uid, 'date_epoch_day' => $epochDay],
                    [
                        'total_calories'      => round($dayTotalCalories, 1),
                        'total_protein'       => round($dayTotalProtein, 1),
                        'total_carbs'         => round($dayTotalCarbs, 1),
                        'total_fat'           => round($dayTotalFat, 1),
                        'total_fiber'         => round($dayTotalFiber, 1),
                        'total_sugar'         => round($dayTotalSugar, 1),
                        'total_saturated_fat' => round($dayTotalSatFat, 1),
                        'total_sodium'        => round($dayTotalSodium, 1),
                        'breakfast_calories'  => round($breakfastCal, 1),
                        'lunch_calories'      => round($lunchCal, 1),
                        'dinner_calories'     => round($dinnerCal, 1),
                        'snacks_calories'     => round($snacksCal, 1),
                        'created_at'          => $logDate,
                        'updated_at'          => $logDate,
                    ]
                );
            }
        }
    }
}
