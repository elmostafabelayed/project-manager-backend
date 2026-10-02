<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $password || strlen($password) < 12) {
            throw new \RuntimeException('Set ADMIN_EMAIL and ADMIN_PASSWORD (at least 12 characters) before creating an admin.');
        }
        User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Jobsy Admin', 'password' => Hash::make($password), 'role_id' => 3]
        );
    }
}
