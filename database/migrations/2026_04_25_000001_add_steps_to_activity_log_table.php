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
            $table->integer('steps')->nullable()->default(null)->after('movingTimeSeconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log_table', function (Blueprint $table) {
            $table->dropColumn('steps');
        });
    }
};
