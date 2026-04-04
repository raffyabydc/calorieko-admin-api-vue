<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: The original migration (2026_04_04_000001) used wrong table names
 * (e.g. 'users' instead of 'user_profile', 'meal_logs' instead of 'meal_log_table').
 * This caused it to silently skip all tables since Schema::hasTable() returned false.
 *
 * This migration uses the CORRECT table names and is idempotent —
 * it safely checks hasColumn() before adding.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'user_profile',
            'meal_log_table',
            'meal_log_item_table',
            'activity_log_table',
            'daily_nutrition_summary_table',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'mobile_updated_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->bigInteger('mobile_updated_at')->nullable()->after('updated_at')
                        ->comment('Epoch millis from mobile client for LWW conflict resolution');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'user_profile',
            'meal_log_table',
            'meal_log_item_table',
            'activity_log_table',
            'daily_nutrition_summary_table',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'mobile_updated_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('mobile_updated_at');
                });
            }
        }
    }
};
