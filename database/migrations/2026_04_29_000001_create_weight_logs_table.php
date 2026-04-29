<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historical weight tracking table.
 * Each row represents a single weight recording event for a user,
 * enabling trend analysis that was previously impossible with
 * the destructive overwrite in user_profile.weight.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weight_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uid');                // Firebase UID — references user_profile.uid
            $table->double('weight');             // Weight in kg
            $table->bigInteger('recorded_at');    // Epoch milliseconds from the mobile client
            $table->timestamps();                 // Laravel created_at / updated_at

            $table->index('uid');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_logs');
    }
};
