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
        // 3. Mock data generators have been removed for Production
        // ────────────────────────────────────────────────────────
        // Keep this file clean so you can safely run:
        // php artisan migrate:fresh --seed
        // on your live server to populate only real Food Items.
    }
}
