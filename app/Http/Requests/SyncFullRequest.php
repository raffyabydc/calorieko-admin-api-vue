<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SyncFullRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We check firebaseUid in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'uid' => 'required|string',
            'last_sync_timestamp' => 'nullable|integer',
            
            // Profile Validation
            'profile' => 'nullable|array',
            'profile.name' => 'required_with:profile|string',
            'profile.email' => 'required_with:profile|string|email',
            'profile.age' => 'required_with:profile|integer|min:0',
            'profile.weight' => 'required_with:profile|numeric|min:0',
            'profile.height' => 'required_with:profile|numeric|min:0',
            'profile.sex' => 'nullable|string',
            'profile.activityLevel' => 'nullable|string',
            'profile.goal' => 'required_with:profile|string',
            'profile.streak' => 'nullable|integer|min:0',
            'profile.level' => 'nullable|integer|min:0',

            // Meals Validation
            'meals' => 'nullable|array',
            'meals.*.uid' => 'required_with:meals|string',
            'meals.*.meal_type' => 'required_with:meals|string|in:Breakfast,Lunch,Dinner,Snacks',
            'meals.*.timestamp' => 'required_with:meals|integer',
            'meals.*.notes' => 'nullable|string',
            'meals.*.items' => 'required_with:meals|array|min:1',
            'meals.*.items.*.food_id' => 'required_with:meals.*.items|integer',
            'meals.*.items.*.dish_name' => 'required_with:meals.*.items|string',
            'meals.*.items.*.weight_grams' => 'required_with:meals.*.items|numeric|min:0',
            'meals.*.items.*.calories' => 'nullable|numeric|min:0',
            'meals.*.items.*.protein' => 'nullable|numeric|min:0',
            'meals.*.items.*.carbs' => 'nullable|numeric|min:0',
            'meals.*.items.*.fiber' => 'nullable|numeric|min:0',
            'meals.*.items.*.sugar' => 'nullable|numeric|min:0',
            'meals.*.items.*.fat' => 'nullable|numeric|min:0',
            'meals.*.items.*.saturated_fat' => 'nullable|numeric|min:0',
            'meals.*.items.*.polyunsaturated_fat' => 'nullable|numeric|min:0',
            'meals.*.items.*.monounsaturated_fat' => 'nullable|numeric|min:0',
            'meals.*.items.*.trans_fat' => 'nullable|numeric|min:0',
            'meals.*.items.*.cholesterol' => 'nullable|numeric|min:0',
            'meals.*.items.*.sodium' => 'nullable|numeric|min:0',
            'meals.*.items.*.potassium' => 'nullable|numeric|min:0',
            'meals.*.items.*.vitamin_a' => 'nullable|numeric|min:0',
            'meals.*.items.*.vitamin_c' => 'nullable|numeric|min:0',
            'meals.*.items.*.calcium' => 'nullable|numeric|min:0',
            'meals.*.items.*.iron' => 'nullable|numeric|min:0',

            // Activities Validation
            'activities' => 'nullable|array',
            'activities.*.uid' => 'required_with:activities|string',
            'activities.*.type' => 'required_with:activities|string|in:meal,workout',
            'activities.*.name' => 'required_with:activities|string',
            'activities.*.timeString' => 'required_with:activities|string',
            'activities.*.weightOrDuration' => 'required_with:activities|string',
            'activities.*.calories' => 'required_with:activities|integer|min:0',
            'activities.*.protein' => 'nullable|integer',
            'activities.*.carbs' => 'nullable|integer',
            'activities.*.fats' => 'nullable|integer',
            'activities.*.sodium' => 'nullable|integer',
            'activities.*.timestamp' => 'required_with:activities|integer',
            'activities.*.distanceKm' => 'nullable|numeric|min:0',
            'activities.*.pace' => 'nullable|numeric|min:0',
            'activities.*.movingTimeSeconds' => 'nullable|integer|min:0',
            'activities.*.mapType' => 'nullable|string',
            'activities.*.notes' => 'nullable|string',
            'activities.*.activityTag' => 'nullable|string',

            // Nutrition Summaries Validation
            'nutrition_summaries' => 'nullable|array',
            'nutrition_summaries.*.uid' => 'required_with:nutrition_summaries|string',
            'nutrition_summaries.*.date_epoch_day' => 'required_with:nutrition_summaries|integer',
            'nutrition_summaries.*.total_calories' => 'nullable|numeric',
            'nutrition_summaries.*.total_protein' => 'nullable|numeric',
            'nutrition_summaries.*.total_carbs' => 'nullable|numeric',
            'nutrition_summaries.*.total_fiber' => 'nullable|numeric',
            'nutrition_summaries.*.total_sugar' => 'nullable|numeric',
            'nutrition_summaries.*.total_fat' => 'nullable|numeric',
            'nutrition_summaries.*.total_saturated_fat' => 'nullable|numeric',
            'nutrition_summaries.*.total_polyunsaturated_fat' => 'nullable|numeric',
            'nutrition_summaries.*.total_monounsaturated_fat' => 'nullable|numeric',
            'nutrition_summaries.*.total_trans_fat' => 'nullable|numeric',
            'nutrition_summaries.*.total_cholesterol' => 'nullable|numeric',
            'nutrition_summaries.*.total_sodium' => 'nullable|numeric',
            'nutrition_summaries.*.total_potassium' => 'nullable|numeric',
            'nutrition_summaries.*.total_vitamin_a' => 'nullable|numeric',
            'nutrition_summaries.*.total_vitamin_c' => 'nullable|numeric',
            'nutrition_summaries.*.total_calcium' => 'nullable|numeric',
            'nutrition_summaries.*.total_iron' => 'nullable|numeric',
            'nutrition_summaries.*.breakfast_calories' => 'nullable|numeric',
            'nutrition_summaries.*.lunch_calories' => 'nullable|numeric',
            'nutrition_summaries.*.dinner_calories' => 'nullable|numeric',
            'nutrition_summaries.*.snacks_calories' => 'nullable|numeric',
        ];
    }

    /**
     * Custom error messages for validation failures.
     */
    public function messages(): array
    {
        return [
            'uid.required' => 'The user ID is required.',
            'profile.name.required_with' => 'Profile name is required when profile data is provided.',
            'meals.*.uid.required_with' => 'Meal owner UID is required for all meals.',
            'activities.*.uid.required_with' => 'Activity owner UID is required for all activities.',
        ];
    }

    /**
     * Handle a failed validation attempt format standard 422 response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors in sync payload',
            'errors' => $validator->errors()
        ], 422));
    }
}
