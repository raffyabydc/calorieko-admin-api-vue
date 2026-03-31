<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_log_table', function (Blueprint $table) {
            $table->double('distanceKm')->nullable();
            $table->double('pace')->nullable();
            $table->bigInteger('movingTimeSeconds')->nullable();
            $table->string('mapType')->nullable();
            $table->text('notes')->nullable();
            $table->string('activityTag')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log_table', function (Blueprint $table) {
            $table->dropColumn([
                'distanceKm',
                'pace',
                'movingTimeSeconds',
                'mapType',
                'notes',
                'activityTag'
            ]);
        });
    }
};
