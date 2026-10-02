<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cookie_authenticated_project_workflow(): void
    {
        foreach (['client', 'freelancer', 'admin'] as $name) {
            Role::forceCreate(['name' => $name]);
        }
        Notification::fake();
        $this->withHeaders(['Origin' => 'http://localhost:3000', 'Referer' => 'http://localhost:3000/']);
        $password = 'Workflow-test-password-12';
        $client = $this->postJson('/api/register', ['name' => 'Workflow client', 'email' => 'client@example.test', 'password' => $password, 'password_confirmation' => $password, 'role_id' => 1])->assertCreated()->json('user');
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('id', $client['id']);
        $project = $this->postJson('/api/projects', ['title' => 'Workflow project', 'description' => 'A complete project description for the workflow test.', 'budget' => 150, 'category' => 'Developpement & tech'])->assertSuccessful()->json();
        $this->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->getJson('/api/profile')->assertUnauthorized();

        $freelancer = $this->postJson('/api/register', ['name' => 'Workflow freelancer', 'email' => 'freelancer@example.test', 'password' => $password, 'password_confirmation' => $password, 'role_id' => 2])->assertCreated()->json('user');
        $this->getJson('/api/projects?available=true')->assertOk()->assertJsonPath('total', 1);
        $proposal = $this->postJson('/api/proposals', ['project_id' => $project['id'], 'price' => 140, 'duration' => 7, 'message' => 'I can complete this project.'])->assertSuccessful()->json();
        $this->getJson('/api/projects?available=true')->assertOk()->assertJsonPath('total', 0);
        $this->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        $this->postJson('/api/login', ['email' => 'client@example.test', 'password' => $password])->assertOk();
        $this->putJson('/api/proposals/'.$proposal['id'].'/accept')->assertOk();
        $conversation = $this->getJson('/api/conversations')->assertOk()->json('0');
        $this->postJson('/api/messages', ['conversation_id' => $conversation['id'], 'content' => 'Welcome to the project'])->assertSuccessful();
        $this->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->postJson('/api/login', ['email' => 'freelancer@example.test', 'password' => $password])->assertOk();
        $this->getJson('/api/conversations/'.$conversation['id'].'/messages')->assertOk()->assertJsonPath('0.content', 'Welcome to the project');
        $this->postJson('/api/messages', ['conversation_id' => $conversation['id'], 'content' => 'The work is ready'])->assertSuccessful();
        $this->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->postJson('/api/login', ['email' => 'client@example.test', 'password' => $password])->assertOk();
        $this->postJson('/api/reviews', ['project_id' => $project['id'], 'reviewed_id' => $freelancer['id'], 'rating' => 5, 'comment' => 'Excellent finished work'])->assertCreated();
        $this->assertDatabaseHas('projects', ['id' => $project['id'], 'status' => 'completed']);
        $this->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->postJson('/api/login', ['email' => 'freelancer@example.test', 'password' => $password])->assertOk();
        $this->postJson('/api/reviews', ['project_id' => $project['id'], 'reviewed_id' => $client['id'], 'rating' => 5, 'comment' => 'Great client communication'])->assertCreated();
    }
}
