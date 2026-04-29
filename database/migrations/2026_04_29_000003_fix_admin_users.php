<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Wipe all existing rows in the users table to clear out
        // any unencrypted/corrupt test accounts that cause DecryptException
        DB::table('users')->truncate();

        // 2. Automatically seed the proper Super Admin account 
        // using Eloquent so that 'name' and 'email' are properly encrypted 
        // by the model casts, and password is hashed.
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@calorieko.ph',
            'password' => Hash::make('calorieko2026'),
            'role' => 'Super Admin',
            'is_active' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->truncate();
    }
};
