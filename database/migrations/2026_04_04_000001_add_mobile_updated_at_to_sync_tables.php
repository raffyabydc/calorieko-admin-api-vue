<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures all sync-enabled tables have a `mobile_updated_at` column
 * for Last Write Wins conflict resolution.
 *
 * This column stores the epoch-millis timestamp from the mobile client
 * and is compared against the server's `updated_at` during sync.
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
                Schema::table($table, function (Blueprint $table) {
                    $table->bigInteger('mobile_updated_at')->nullable()->after('updated_at')
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
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('mobile_updated_at');
                });
            }
        }
    }
};
