<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'slug',
    'description',
    'image_url',
    'technologies',
    'live_url',
    'github_url',
    'featured',
    'published',
    'sort_order',
    'role',
    'client_region',
    'problem',
    'solution',
    'outcome',
    'confidential',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidential' => 'boolean',
            'featured' => 'boolean',
            'published' => 'boolean',
            'sort_order' => 'integer',
            'technologies' => 'array',
        ];
    }

    /**
     * Limit the query to publicly visible projects.
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('published', true);
    }

    /**
     * Apply the default portfolio ordering.
     */
    public function scopeOrdered(Builder $query): void
    {
        $query
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}
