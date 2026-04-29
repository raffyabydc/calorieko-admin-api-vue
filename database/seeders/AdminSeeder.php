<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin exists
        if (!User::where('email', 'admin@calorieko.ph')->exists()) {
            User::create([
                'name' => 'System Administrator',
                'email' => 'admin@calorieko.ph',
                'password' => Hash::make('calorieko2026'),
                'role' => 'Super Admin',
                'is_active' => true,
            ]);
        }
    }
}
