<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $randomStr = Str::random(6);
        
        return [
            'name' => 'User ' . $randomStr,
            'email' => 'user_' . $randomStr . '@gmail.com',
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => 2, // Default to freelancer
        ];
    }
}