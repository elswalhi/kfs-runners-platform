<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RaceSeeder::class);

        $email = env('KFS_ADMIN_EMAIL');
        $password = env('KFS_ADMIN_PASSWORD');
        if (!$email || !$password || strlen($password) < 12) {
            throw new RuntimeException('Set KFS_ADMIN_EMAIL and a KFS_ADMIN_PASSWORD of at least 12 characters in .env before running db:seed.');
        }
        User::query()->updateOrCreate(['email' => $email], [
            'name' => env('KFS_ADMIN_NAME', 'KFS Administrator'),
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
