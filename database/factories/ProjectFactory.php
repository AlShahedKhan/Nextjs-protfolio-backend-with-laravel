<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'image_url' => fake()->imageUrl(1200, 675, 'technology'),
            'technologies' => ['Laravel', 'PostgreSQL', 'Redis'],
            'live_url' => fake()->optional()->url(),
            'github_url' => fake()->optional()->url(),
            'featured' => fake()->boolean(),
            'published' => true,
            'sort_order' => fake()->numberBetween(0, 50),
            'role' => fake()->jobTitle(),
            'client_region' => fake()->country(),
            'problem' => fake()->sentence(),
            'solution' => fake()->sentence(),
            'outcome' => fake()->sentence(),
            'confidential' => fake()->boolean(),
        ];
    }
}
