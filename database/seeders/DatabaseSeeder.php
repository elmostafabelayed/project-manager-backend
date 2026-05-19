<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Review;
use App\Models\Notification;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run()
    {
        // 1. Static seeders
        $this->call(RoleSeeder::class);
        $this->call(SkillSeeder::class);
        $this->call(AdminSeeder::class); // Seeds 1 Admin User (role_id = 3)

        // 2. Users Seeding (Target: exactly 15 users in database)
        // Since Admin is already created, we need 14 more users.
        // Let's create 7 Clients (role_id = 1) and 7 Freelancers (role_id = 2).
        $clients = User::factory()->count(7)->create(['role_id' => 1]);
        $freelancers = User::factory()->count(7)->create(['role_id' => 2]);
        $users = User::all(); // Now we have exactly 15 users total (1 admin + 7 clients + 7 freelancers)

        // 3. Profiles Seeding (Target: exactly 15 profiles in database)
        // Create exactly one profile for each of the 15 users.
        foreach ($users as $user) {
            Profile::factory()->create([
                'user_id' => $user->id,
            ]);
        }

        // 4. Skills Pivot Seeding (Pivot table skill_user)
        // Assign 3-5 random skills to each freelancer.
        $skills = Skill::all();
        foreach ($freelancers as $freelancer) {
            $randomSkills = $skills->random(rand(3, 5))->pluck('id');
            $freelancer->skills()->attach($randomSkills);
        }

        // 5. Projects Seeding (Target: exactly 15 projects in database)
        // Create 15 projects, randomly assigned to our 7 clients.
        $projects = collect();
        for ($i = 0; $i < 15; $i++) {
            $projects->push(Project::factory()->create([
                'client_id' => $clients->random()->id,
            ]));
        }

        // 6. Proposals Seeding (Target: exactly 15 proposals in database)
        // Pair random projects and freelancers to create exactly 15 unique proposals.
        $proposalPairs = [];
        while (count($proposalPairs) < 15) {
            $proj = $projects->random();
            $free = $freelancers->random();
            $key = "{$proj->id}-{$free->id}";
            if (!isset($proposalPairs[$key])) {
                $proposalPairs[$key] = true;
                Proposal::factory()->create([
                    'project_id' => $proj->id,
                    'freelancer_id' => $free->id,
                    'price' => round($proj->budget * rand(80, 120) / 100, 2),
                ]);
            }
        }

        // 7. Contracts Seeding (Target: exactly 15 contracts in database)
        // Assign exactly 1 contract per project.
        for ($i = 0; $i < 15; $i++) {
            $project = $projects[$i];
            // Find a freelancer who proposed on this project, or select a random freelancer
            $bidder = Proposal::where('project_id', $project->id)->first()?->freelancer_id 
                      ?? $freelancers->random()->id;

            Contract::factory()->create([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'freelancer_id' => $bidder,
                'status' => fake()->randomElement(['active', 'completed', 'terminated']),
            ]);
        }

        // 8. Conversations Seeding (Target: exactly 15 conversations in database)
        // Set up exactly 1 conversation for each of the 15 projects.
        for ($i = 0; $i < 15; $i++) {
            $project = $projects[$i];
            // Find the contracted freelancer for this project
            $contract = Contract::where('project_id', $project->id)->first();
            $freelancerId = $contract ? $contract->freelancer_id : $freelancers->random()->id;

            Conversation::factory()->create([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'freelancer_id' => $freelancerId,
            ]);
        }

        // 9. Messages Seeding (Target: exactly 15 messages in database)
        // Generate exactly 1 message in each of the 15 conversations.
        $conversations = Conversation::all();
        foreach ($conversations as $conversation) {
            $senderId = fake()->boolean() ? $conversation->client_id : $conversation->freelancer_id;
            if (!$senderId) {
                $senderId = $users->random()->id;
            }

            Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
            ]);
        }

        // 10. Reviews Seeding (Target: exactly 15 reviews in database)
        // Create exactly 1 review for each of the 15 contracts (client reviews freelancer).
        $contracts = Contract::all();
        foreach ($contracts as $contract) {
            Review::factory()->create([
                'project_id' => $contract->project_id,
                'reviewer_id' => $contract->client_id,
                'reviewed_id' => $contract->freelancer_id,
            ]);
        }

        // 11. Notifications Seeding (Target: exactly 15 notifications in database)
        // Distribute exactly 15 notifications among random users.
        for ($i = 0; $i < 15; $i++) {
            Notification::factory()->create([
                'user_id' => $users->random()->id,
            ]);
        }
    }
}
