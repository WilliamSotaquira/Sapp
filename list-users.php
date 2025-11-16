<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = DB::table('users')->get(['id', 'name', 'email', 'role']);

foreach ($users as $user) {
    echo $user->id . ' - ' . $user->name . ' (' . $user->email . ') - Role: ' . ($user->role ?? 'no asignado') . PHP_EOL;
}
