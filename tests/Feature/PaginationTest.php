<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_are_bounded_ordered_and_filtered_across_pages(): void
    {
        Role::forceCreate(['id' => 1, 'name' => 'client']);
        $client = User::create(['name' => 'Client', 'email' => 'client@example.test', 'password' => 'password123', 'role_id' => 1]);
        for ($i = 1; $i <= 25; $i++) {
            Project::create(['client_id' => $client->id, 'title' => 'Project '.$i, 'description' => 'A useful project description', 'category' => 'Tech', 'budget' => 100]);
        }
        $first = $this->getJson('/api/projects')->assertOk()->assertJsonCount(12, 'data')->assertJsonPath('last_page', 3)->assertJsonPath('total', 25)->json('data');
        $second = $this->getJson('/api/projects?page=2')->assertOk()->assertJsonCount(12, 'data')->json('data');
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->getJson('/api/projects?search=Project%2025')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.title', 'Project 25');
        $this->getJson('/api/projects?category=Other')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/projects?per_page=10000')->assertUnprocessable();
        $this->getJson('/api/projects?page=-1')->assertUnprocessable();
    }
}
