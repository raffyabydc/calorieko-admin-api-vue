<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FoodItemController extends Controller
{
    /**
     * Full validation rules for all nutrient fields.
     * Shared across sync, store, and update endpoints.
     */
    private function fullNutrientRules(bool $requireNames = true): array
    {
        $nameRule = $requireNames ? 'required|string' : 'sometimes|string';
        return [
            'name_en'                      => $nameRule,
            'name_ph'                      => $nameRule,
            'category'                     => $requireNames ? 'required|string' : 'sometimes|string',
            'ml_label'                     => 'nullable|string',
            'calories_per_100g'            => 'nullable|numeric',
            'protein_per_100g'             => 'nullable|numeric',
            'carbs_per_100g'               => 'nullable|numeric',
            'fiber_per_100g'               => 'nullable|numeric',
            'sugar_per_100g'               => 'nullable|numeric',
            'fat_per_100g'                 => 'nullable|numeric',
            'saturated_fat_per_100g'       => 'nullable|numeric',
            'polyunsaturated_fat_per_100g' => 'nullable|numeric',
            'monounsaturated_fat_per_100g' => 'nullable|numeric',
            'trans_fat_per_100g'           => 'nullable|numeric',
            'cholesterol_per_100g'         => 'nullable|numeric',
            'sodium_per_100g'              => 'nullable|numeric',
            'potassium_per_100g'           => 'nullable|numeric',
            'vitamin_a_per_100g'           => 'nullable|numeric',
            'vitamin_c_per_100g'           => 'nullable|numeric',
            'calcium_per_100g'             => 'nullable|numeric',
            'iron_per_100g'                => 'nullable|numeric',
            'data_source'                  => 'nullable|string',
        ];
    }

    /**
     * Sync: Upsert a food item from the mobile app.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate(array_merge(
            ['food_id' => 'nullable|integer'],
            $this->fullNutrientRules()
        ));

        $food = FoodItem::updateOrCreate(
            ['name_en' => $data['name_en'], 'name_ph' => $data['name_ph']],
            $data
        );

        return response()->json($food, 200);
    }

    /**
     * Admin: List all food items with optional search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FoodItem::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ph', 'like', "%{$search}%");
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        return response()->json($query->get());
    }

    /**
     * Admin: Show a single food item.
     */
    public function show(int $id): JsonResponse
    {
        $food = FoodItem::findOrFail($id);
        return response()->json($food);
    }

    /**
     * Admin: Create a new food item.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->fullNutrientRules());

        $food = FoodItem::create($data);

        $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
        \App\Models\SystemLog::log($adminEmail, "Added Food Item", "Food ID: {$food->food_id}", 'Success', request()->ip(), "Admin added food: {$food->name_en}");

        return response()->json($food, 201);
    }

    /**
     * Admin: Update an existing food item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $food = FoodItem::findOrFail($id);

        $data = $request->validate($this->fullNutrientRules());

        $food->update($data);

        $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
        \App\Models\SystemLog::log($adminEmail, "Updated Food Item", "Food ID: {$food->food_id}", 'Success', request()->ip(), "Admin updated food: {$food->name_en}");

        return response()->json($food);
    }

    /**
     * Admin: Delete a food item.
     */
    public function destroy(int $id): JsonResponse
    {
        $food = FoodItem::findOrFail($id);
        $name = $food->name_en;
        $food->delete();

        $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
        \App\Models\SystemLog::log($adminEmail, "Deleted Food Item", "Food ID: {$id}", 'Success', request()->ip(), "Admin deleted food: {$name}");

        return response()->json(['message' => 'Food item successfully deleted.']);
    }

    /**
     * Admin: Bulk import food items from a CSV upload.
     *
     * Expected CSV columns (header row required):
     *   ml_label, name_en, name_ph, category, calories_kcal, protein_g,
     *   carbs_g, fat_g, fiber_g, sugar_g, saturated_fat_g,
     *   polyunsaturated_fat_g, monounsaturated_fat_g, trans_fat_g,
     *   cholesterol_mg, sodium_mg, potassium_mg, vitamin_a_mcg,
     *   vitamin_c_mg, calcium_mg, iron_mg, data_source
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));

        // Remove header row
        $header = array_shift($rows);

        // Map CSV column names → DB column names
        $columnMap = [
            'ml_label'                => 'ml_label',
            'name_en'                 => 'name_en',
            'name_ph'                 => 'name_ph',
            'category'                => 'category',
            'calories_kcal'           => 'calories_per_100g',
            'protein_g'               => 'protein_per_100g',
            'carbs_g'                 => 'carbs_per_100g',
            'fat_g'                   => 'fat_per_100g',
            'fiber_g'                 => 'fiber_per_100g',
            'sugar_g'                 => 'sugar_per_100g',
            'saturated_fat_g'         => 'saturated_fat_per_100g',
            'polyunsaturated_fat_g'   => 'polyunsaturated_fat_per_100g',
            'monounsaturated_fat_g'   => 'monounsaturated_fat_per_100g',
            'trans_fat_g'             => 'trans_fat_per_100g',
            'cholesterol_mg'          => 'cholesterol_per_100g',
            'sodium_mg'               => 'sodium_per_100g',
            'potassium_mg'            => 'potassium_per_100g',
            'vitamin_a_mcg'           => 'vitamin_a_per_100g',
            'vitamin_c_mg'            => 'vitamin_c_per_100g',
            'calcium_mg'              => 'calcium_per_100g',
            'iron_mg'                 => 'iron_per_100g',
            'data_source'             => 'data_source',
        ];

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if (count($row) < 4) {
                $skipped++;
                continue;
            }

            // Build associative array from header
            $csvData = [];
            foreach ($header as $colIdx => $colName) {
                $cleanName = trim(strtolower($colName));
                if (isset($columnMap[$cleanName]) && isset($row[$colIdx])) {
                    $csvData[$columnMap[$cleanName]] = trim($row[$colIdx]);
                }
            }

            // Validate required fields
            if (empty($csvData['name_en']) || empty($csvData['category'])) {
                $skipped++;
                $errors[] = "Row " . ($index + 2) . ": Missing name_en or category";
                continue;
            }

            // Default ml_label and data_source
            $csvData['ml_label']    = $csvData['ml_label'] ?? 'manual_entry';
            $csvData['data_source'] = $csvData['data_source'] ?? 'DOST_FNRI_FCT';

            // Cast numeric fields
            $numericFields = [
                'calories_per_100g', 'protein_per_100g', 'carbs_per_100g',
                'fat_per_100g', 'fiber_per_100g', 'sugar_per_100g',
                'saturated_fat_per_100g', 'polyunsaturated_fat_per_100g',
                'monounsaturated_fat_per_100g', 'trans_fat_per_100g',
                'cholesterol_per_100g', 'sodium_per_100g', 'potassium_per_100g',
                'vitamin_a_per_100g', 'vitamin_c_per_100g',
                'calcium_per_100g', 'iron_per_100g',
            ];
            foreach ($numericFields as $field) {
                if (isset($csvData[$field])) {
                    $csvData[$field] = (float) $csvData[$field];
                }
            }

            try {
                FoodItem::updateOrCreate(
                    ['name_en' => $csvData['name_en'], 'name_ph' => $csvData['name_ph'] ?? ''],
                    $csvData
                );
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $adminEmail = config('app.admin_email') ?? 'admin@calorieko.com';
        \App\Models\SystemLog::log(
            $adminEmail, "Bulk Import Food Items",
            "Imported: {$imported}, Skipped: {$skipped}",
            $skipped > 0 ? 'Partial' : 'Success',
            request()->ip(),
            "Admin bulk imported {$imported} food items from CSV"
        );

        return response()->json([
            'message'  => "Import complete: {$imported} imported, {$skipped} skipped.",
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => array_slice($errors, 0, 10), // Return first 10 errors
        ]);
    }
}
