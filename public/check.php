<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::all()->first(function ($u) {
    return strtolower($u->email) === 'admin@calorieko.ph';
});

if ($user) {
    echo "User exists. Name: " . $user->name . "\n";
    echo "Role: " . $user->role . "\n";
    echo "Is Active: " . $user->is_active . "\n";
    $matches = \Illuminate\Support\Facades\Hash::check('calorieko2026', $user->password) ? 'YES' : 'NO';
    echo "Password match 'calorieko2026'?: " . $matches . "\n";
} else {
    echo "User does not exist.\n";
}
