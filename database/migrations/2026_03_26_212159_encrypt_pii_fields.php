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
        Schema::table('users', function (Blueprint $table) {
            // Drop unique index first to allow changing to text
            $table->dropUnique(['email']);
            $table->text('name')->change();
            $table->text('email')->change();
        });

        Schema::table('user_profile', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('email')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('email')->change();
            $table->unique('email');
        });

        Schema::table('user_profile', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('email')->change();
        });
    }
};
