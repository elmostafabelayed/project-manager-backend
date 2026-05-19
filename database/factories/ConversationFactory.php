<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'client_id' => User::factory(),
            'freelancer_id' => User::factory(),
        ];
    }
}
