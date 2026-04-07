<?php

use App\Models\Project;

it('returns only published projects on the public list endpoint', function () {
    Project::factory()->create([
        'title' => 'Published Featured',
        'slug' => 'published-featured',
        'featured' => true,
        'published' => true,
        'sort_order' => 2,
    ]);

    Project::factory()->create([
        'title' => 'Published Standard',
        'slug' => 'published-standard',
        'featured' => false,
        'published' => true,
        'sort_order' => 1,
    ]);

    Project::factory()->create([
        'title' => 'Draft Project',
        'slug' => 'draft-project',
        'published' => false,
    ]);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'published-featured')
        ->assertJsonPath('data.1.slug', 'published-standard')
        ->assertJsonMissing([
            'slug' => 'draft-project',
        ]);
});

it('supports featured filtering on the public list endpoint', function () {
    Project::factory()->create([
        'slug' => 'featured-project',
        'featured' => true,
        'published' => true,
    ]);

    Project::factory()->create([
        'slug' => 'normal-project',
        'featured' => false,
        'published' => true,
    ]);

    $this->getJson('/api/v1/projects?featured=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'featured-project');
});

it('returns a single published project by slug for public view', function () {
    $project = Project::factory()->create([
        'title' => 'Orfa AI',
        'slug' => 'orfa-ai',
        'published' => true,
        'confidential' => true,
    ]);

    $this->getJson('/api/v1/projects/orfa-ai')
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.slug', 'orfa-ai')
        ->assertJsonPath('data.confidential', true);
});

it('returns not found for unpublished public projects', function () {
    Project::factory()->create([
        'slug' => 'hidden-project',
        'published' => false,
    ]);

    $this->getJson('/api/v1/projects/hidden-project')
        ->assertNotFound();
});
