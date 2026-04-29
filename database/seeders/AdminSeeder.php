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
        // Check if admin exists (filtering in-memory because email is encrypted)
        $exists = User::all()->contains(function ($u) {
            return strtolower($u->email) === 'admin@calorieko.ph';
        });

        if (!$exists) {
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
