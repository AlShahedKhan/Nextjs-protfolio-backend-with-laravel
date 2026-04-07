<?php

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function projectPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Orfa AI',
        'slug' => 'orfa-ai',
        'description' => 'Built an AI chatbot platform for a USA client with custom AI agents, JWT-secured APIs, session tracking, user feedback flow, and usage analytics across a scalable Laravel backend.',
        'image_url' => 'https://api.example.com/storage/projects/orfa-ai.jpg',
        'technologies' => ['Laravel', 'OpenAI API', 'Redis', 'Docker', 'AWS', 'JWT'],
        'live_url' => null,
        'github_url' => null,
        'featured' => true,
        'published' => true,
        'sort_order' => 1,
        'role' => 'Senior Laravel Developer',
        'client_region' => 'USA',
        'problem' => 'Needed a scalable AI chatbot backend',
        'solution' => 'Designed modular Laravel APIs with JWT auth, session tracking, and analytics support',
        'outcome' => 'Delivered a scalable backend ready for production use',
        'confidential' => true,
    ], $overrides);
}

it('requires authentication for admin project routes', function () {
    $this->getJson('/api/v1/admin/projects')->assertUnauthorized();
});

it('forbids non-admin users from admin project routes', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/projects')->assertForbidden();
});

it('allows an admin to create a project', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/v1/admin/projects', projectPayload());

    $response
        ->assertCreated()
        ->assertJsonPath('data.slug', 'orfa-ai')
        ->assertJsonPath('data.featured', true)
        ->assertJsonPath('data.confidential', true);

    $this->assertDatabaseHas('projects', [
        'slug' => 'orfa-ai',
        'published' => true,
        'confidential' => true,
    ]);
});

it('returns a paginated project list for admins', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    Project::factory()->count(3)->create();

    $this->getJson('/api/v1/admin/projects?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('allows an admin to view a project by slug', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $project = Project::factory()->create([
        'slug' => 'orfa-ai',
        'title' => 'Orfa AI',
    ]);

    $this->getJson('/api/v1/admin/projects/orfa-ai')
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.slug', 'orfa-ai');
});

it('allows an admin to update a project', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $project = Project::factory()->create([
        'slug' => 'orfa-ai',
    ]);

    $response = $this->patchJson('/api/v1/admin/projects/orfa-ai', [
        'title' => 'Orfa AI Platform',
        'featured' => false,
        'technologies' => ['Laravel', 'OpenAI API'],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.title', 'Orfa AI Platform')
        ->assertJsonPath('data.featured', false)
        ->assertJsonPath('data.technologies.1', 'OpenAI API');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'title' => 'Orfa AI Platform',
        'featured' => false,
    ]);
});

it('allows an admin to delete a project', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $project = Project::factory()->create([
        'slug' => 'orfa-ai',
    ]);

    $this->deleteJson('/api/v1/admin/projects/orfa-ai')->assertNoContent();

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});
