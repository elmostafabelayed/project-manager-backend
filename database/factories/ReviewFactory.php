<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'reviewer_id' => User::factory(),
            'reviewed_id' => User::factory(),
            'project_id' => Project::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->realText(150),
        ];
    }
}
