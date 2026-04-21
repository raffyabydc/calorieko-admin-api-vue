<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds ml_label and data_source columns to FOOD_TABLE to match
 * the mobile app's Room entity (FoodItem.kt).
 *
 * ml_label    — the TFLite model class label (e.g. "sinigang_pork").
 *               Defaults to "manual_entry" for manually-added items.
 * data_source — attribution tag for the nutritional data origin
 *               (e.g. "DOST_FNRI_FCT", "USDA_FNDDS").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('FOOD_TABLE', function (Blueprint $table) {
            $table->string('ml_label')->default('manual_entry')->after('category');
            $table->string('data_source')->default('DOST_FNRI_FCT')->after('iron_per_100g');
        });
    }

    public function down(): void
    {
        Schema::table('FOOD_TABLE', function (Blueprint $table) {
            $table->dropColumn(['ml_label', 'data_source']);
        });
    }
};
