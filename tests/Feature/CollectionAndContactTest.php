<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionAndContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_is_stored_privately_and_newsletter_is_idempotent(): void
    {
        $data = ['name' => 'Contact visitor', 'email' => 'visitor@example.test', 'subject' => 'A support request', 'message' => 'Please help me with my account.'];
        $this->postJson('/api/contact', $data)->assertCreated();
        $this->assertDatabaseHas('contact_messages', $data);
        $this->getJson('/api/admin/contact-messages')->assertUnauthorized();
        $this->postJson('/api/contact', ['name' => 'Invalid'])->assertUnprocessable();
        $this->postJson('/api/newsletter', ['email' => 'visitor@example.test'])->assertOk();
        $this->postJson('/api/newsletter', ['email' => 'visitor@example.test'])->assertOk();
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_message_history_and_newer_cursor_are_bounded_and_ordered(): void
    {
        Role::forceCreate(['id' => 1, 'name' => 'client']);
        Role::forceCreate(['id' => 2, 'name' => 'freelancer']);
        $client = User::create(['name' => 'Client', 'email' => 'client@example.test', 'password' => 'password123', 'role_id' => 1]);
        $freelancer = User::create(['name' => 'Freelancer', 'email' => 'freelancer@example.test', 'password' => 'password123', 'role_id' => 2]);
        $project = Project::create(['client_id' => $client->id, 'title' => 'A project', 'description' => 'A useful project description', 'budget' => 100]);
        $conversation = Conversation::create(['project_id' => $project->id, 'client_id' => $client->id, 'freelancer_id' => $freelancer->id]);
        for ($i = 1; $i <= 120; $i++) {
            Message::create(['conversation_id' => $conversation->id, 'sender_id' => $client->id, 'content' => 'Message '.$i]);
        }
        $path = '/api/conversations/'.$conversation->id.'/messages';
        $this->actingAs($client)->getJson($path)->assertOk()->assertJsonCount(50)->assertJsonPath('0.content', 'Message 71')->assertJsonPath('49.content', 'Message 120');
        $this->getJson($path.'?before_id=71')->assertOk()->assertJsonCount(50)->assertJsonPath('0.content', 'Message 21');
        $this->getJson($path.'?after_id=0')->assertOk()->assertJsonCount(100)->assertJsonPath('99.content', 'Message 100');
        $this->getJson($path.'?after_id=100')->assertOk()->assertJsonCount(20)->assertJsonPath('0.content', 'Message 101');
    }

    public function test_conversation_cursor_and_direct_selection_remain_private(): void
    {
        Role::forceCreate(['id' => 1, 'name' => 'client']);
        Role::forceCreate(['id' => 2, 'name' => 'freelancer']);
        $client = User::create(['name' => 'Client', 'email' => 'client@example.test', 'password' => 'password123', 'role_id' => 1]);
        $outsider = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'password123', 'role_id' => 1]);
        $freelancer = User::create(['name' => 'Freelancer', 'email' => 'freelancer@example.test', 'password' => 'password123', 'role_id' => 2]);
        for ($i = 1; $i <= 52; $i++) {
            $project = Project::create(['client_id' => $client->id, 'title' => 'Project '.$i, 'description' => 'Description', 'budget' => 100]);
            Conversation::create(['project_id' => $project->id, 'client_id' => $client->id, 'freelancer_id' => $freelancer->id]);
        }
        $this->actingAs($client)->getJson('/api/conversations')->assertOk()->assertJsonCount(50)->assertJsonPath('0.id', 52)->assertJsonPath('49.id', 3);
        $this->getJson('/api/conversations?before_id=3')->assertOk()->assertJsonCount(2)->assertJsonPath('0.id', 2);
        $this->getJson('/api/conversations?conversation_id=1')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', 1);
        $this->actingAs($outsider)->getJson('/api/conversations?conversation_id=1')->assertOk()->assertJsonCount(0);
    }

    public function test_notification_counts_include_unread_items_outside_the_page(): void
    {
        Role::forceCreate(['id' => 1, 'name' => 'client']);
        $user = User::create(['name' => 'Client', 'email' => 'client@example.test', 'password' => 'password123', 'role_id' => 1]);
        for ($i = 0; $i < 25; $i++) {
            $user->notifications()->create(['type' => 'message_new', 'data' => ['conversation_id' => 1]]);
        }
        $this->actingAs($user)->getJson('/api/notifications')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('unread_count', 25)->assertJsonPath('unread_messages', 25);
        $this->putJson('/api/notifications/mark-all-read')->assertOk();
        $this->getJson('/api/notifications')->assertOk()->assertJsonPath('unread_count', 0);
    }
}
