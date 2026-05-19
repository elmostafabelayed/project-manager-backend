<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $categories = ['Design & creative', 'Developpement & tech', 'AI & emerging tech', 'Marketing', 'Writing & content', 'Admin & support'];
        return [
            'title' => fake()->realText(50),
            'description' => fake()->realText(400),
            'budget' => fake()->randomFloat(2, 50, 5000),
            'category' => fake()->randomElement($categories),
            'client_id' => User::factory(),
            'status' => fake()->randomElement(['open', 'in_progress', 'completed', 'closed']),
        ];
    }
}
