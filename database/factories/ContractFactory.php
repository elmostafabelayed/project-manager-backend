<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'client_id' => User::factory(),
            'freelancer_id' => User::factory(),
            'status' => fake()->randomElement(['active', 'completed', 'terminated']),
        ];
    }
}
