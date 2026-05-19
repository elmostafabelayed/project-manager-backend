<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'location' => fake()->city() . ', ' . fake()->country(),
            'profile_picture' => 'https://i.pravatar.cc/150?u=' . fake()->uuid(),
            'hourly_rate' => fake()->randomFloat(2, 10, 150),
        ];
    }
}
