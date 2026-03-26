<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EncryptExistingPii extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:encrypt-existing-pii';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt existing names and emails in the database for User and UserProfile models.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting PII encryption process (Raw DB Mode)...');

        // 1. Encrypt Users table
        $users = \Illuminate\Support\Facades\DB::table('users')->get();
        $this->info("Found {$users->count()} users in raw database.");
        $userCount = 0;
        foreach ($users as $user) {
            try {
                // Check if already encrypted
                \Illuminate\Support\Facades\Crypt::decryptString($user->name);
                \Illuminate\Support\Facades\Crypt::decryptString($user->email);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Not encrypted, let's encrypt
                \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update([
                    'name' => \Illuminate\Support\Facades\Crypt::encryptString($user->name),
                    'email' => \Illuminate\Support\Facades\Crypt::encryptString($user->email),
                ]);
                $userCount++;
            }
        }
        $this->info("Encrypted {$userCount} users.");

        // 2. Encrypt user_profile table
        $profiles = \Illuminate\Support\Facades\DB::table('user_profile')->get();
        $this->info("Found {$profiles->count()} profiles in raw database.");
        $profileCount = 0;
        foreach ($profiles as $profile) {
            try {
                \Illuminate\Support\Facades\Crypt::decryptString($profile->name);
                \Illuminate\Support\Facades\Crypt::decryptString($profile->email);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                \Illuminate\Support\Facades\DB::table('user_profile')->where('uid', $profile->uid)->update([
                    'name' => \Illuminate\Support\Facades\Crypt::encryptString($profile->name),
                    'email' => \Illuminate\Support\Facades\Crypt::encryptString($profile->email),
                ]);
                $profileCount++;
            }
        }
        $this->info("Encrypted {$profileCount} user profiles.");

        $this->info('Raw PII encryption process completed.');
    }
}
