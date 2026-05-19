<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = ['proposal_new', 'proposal_accepted', 'message_new', 'contract_completed'];
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement($types),
            'data' => [
                'project_title' => fake()->sentence(3),
                'message' => fake()->sentence(6),
                'actor_name' => fake()->name()
            ],
            'read_at' => fake()->optional()->dateTimeThisMonth(),
        ];
    }
}
