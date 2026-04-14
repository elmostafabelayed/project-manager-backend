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
        User::updateOrCreate(
            ['email' => 'admin@jobsy.com'],
            [
                'name' => 'Jobsy Admin',
                'password' => Hash::make('admin123'),
                'role_id' => 3 // Admin role
            ]
        );
    }
}
