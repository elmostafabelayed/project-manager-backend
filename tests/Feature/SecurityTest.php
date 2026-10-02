<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['client', 'freelancer', 'admin'] as $name) {
            Role::forceCreate(['name' => $name]);
        }
        Notification::fake();
    }

    private function user(int $role = 1): User
    {
        return User::create(['name' => 'Test user', 'email' => uniqid().'@example.com', 'password' => 'password123', 'role_id' => $role]);
    }

    private function project(User $client): Project
    {
        return Project::create(['client_id' => $client->id, 'title' => 'A project', 'description' => str_repeat('Description ', 4), 'budget' => 100]);
    }

    private function proposal(Project $project, User $freelancer): Proposal
    {
        return Proposal::create(['project_id' => $project->id, 'freelancer_id' => $freelancer->id, 'price' => 90, 'duration' => 10, 'message' => 'I can do this project.']);
    }

    public function test_proposals_are_private(): void
    {
        $client = $this->user();
        $project = $this->project($client);
        $this->getJson("/api/projects/{$project->id}/proposals")->assertUnauthorized();
        $this->actingAs($this->user())->getJson("/api/projects/{$project->id}/proposals")->assertForbidden();
        $this->actingAs($client)->getJson("/api/projects/{$project->id}/proposals")->assertOk();
    }

    public function test_project_owner_cannot_be_changed_by_mass_assignment(): void
    {
        $client = $this->user();
        $other = $this->user();
        $project = $this->project($client);
        $this->actingAs($client)->putJson("/api/projects/{$project->id}", ['title' => 'Updated', 'client_id' => $other->id, 'status' => 'completed'])->assertOk();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'client_id' => $client->id, 'status' => 'open']);
        $this->actingAs($other)->deleteJson("/api/projects/{$project->id}")->assertForbidden();
    }

    public function test_freelancer_cannot_create_projects_or_access_admin(): void
    {
        $this->actingAs($this->user(2))->postJson('/api/projects', [])->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_acceptance_is_atomic_and_cannot_be_repeated(): void
    {
        $client = $this->user();
        $project = $this->project($client);
        $proposal = $this->proposal($project, $this->user(2));
        $this->actingAs($this->user())->putJson("/api/proposals/{$proposal->id}/accept")->assertForbidden();
        $this->actingAs($client)->putJson("/api/proposals/{$proposal->id}/accept")->assertOk();
        $this->putJson("/api/proposals/{$proposal->id}/accept")->assertConflict();
        $this->putJson("/api/proposals/{$proposal->id}/reject")->assertConflict();
        $this->assertDatabaseCount('contracts', 1);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'active']);
    }

    public function test_reviews_require_contract_participants_and_complete_the_project(): void
    {
        $client = $this->user();
        $freelancer = $this->user(2);
        $project = $this->project($client);
        $project->status = 'active';
        $project->save();
        Contract::create(['project_id' => $project->id, 'client_id' => $client->id, 'freelancer_id' => $freelancer->id, 'status' => 'active']);
        $payload = ['project_id' => $project->id, 'reviewed_id' => $freelancer->id, 'rating' => 5, 'comment' => 'Excellent work'];
        $this->actingAs($this->user())->postJson('/api/reviews', $payload)->assertForbidden();
        $this->actingAs($client)->postJson('/api/reviews', $payload)->assertCreated();
        $this->postJson('/api/reviews', $payload)->assertConflict();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'completed']);
        $this->assertDatabaseHas('contracts', ['project_id' => $project->id, 'status' => 'completed']);
    }

    public function test_conversation_project_filter_cannot_match_another_project(): void
    {
        $client = $this->user();
        $freelancer = $this->user(2);
        $first = $this->project($client);
        $second = $this->project($client);
        $existing = Conversation::create(['project_id' => $first->id, 'client_id' => $client->id, 'freelancer_id' => $freelancer->id]);
        $this->actingAs($client)->postJson('/api/conversations/show-or-create', ['user_id' => $freelancer->id, 'project_id' => $second->id])->assertOk()->assertJsonPath('project_id', $second->id);
        $this->actingAs($this->user())->getJson("/api/conversations/{$existing->id}/messages")->assertForbidden();
        $this->postJson('/api/messages', ['conversation_id' => $existing->id, 'content' => 'Private message'])->assertForbidden();
    }

    public function test_public_users_do_not_leak_email_addresses(): void
    {
        $freelancer = $this->user(2);
        $project = $this->project($this->user());
        $this->getJson('/api/freelancers')->assertOk()->assertJsonMissingPath('data.0.email');
        $this->getJson("/api/projects/{$project->id}")->assertOk()->assertJsonMissingPath('client.email');
        $this->getJson("/api/users/{$freelancer->id}/profile")->assertOk()->assertJsonMissingPath('email');
    }

    public function test_notification_json_filter_is_portable_and_scoped(): void
    {
        $user = $this->user();
        $other = $this->user();
        foreach ([$user, $other] as $recipient) {
            $recipient->notifications()->create(['type' => 'message_new', 'data' => ['conversation_id' => 42]]);
        }
        $this->actingAs($user)->putJson('/api/notifications/conversation/42/read')->assertOk();
        $this->assertNotNull($user->notifications()->first()->read_at);
        $this->assertNull($other->notifications()->first()->read_at);
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'invalid'])->assertUnauthorized();
        }
        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'invalid'])->assertStatus(429);
    }
}
