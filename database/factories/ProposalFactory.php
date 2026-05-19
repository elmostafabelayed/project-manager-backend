<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'freelancer_id' => User::factory(),
            'price' => fake()->randomFloat(2, 50, 4000),
            'duration' => fake()->numberBetween(1, 30),
            'message' => fake()->realText(200),
            'status' => fake()->randomElement(['pending', 'accepted', 'rejected']),
            'source' => fake()->randomElement(['freelancer', 'client']),
            'response_message' => fake()->optional()->sentence(),
        ];
    }
}
