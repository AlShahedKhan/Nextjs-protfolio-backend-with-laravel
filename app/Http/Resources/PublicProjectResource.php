<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class PublicProjectResource extends JsonResource
{
    /**
     * @return array<string, array<int, string>|bool|int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'technologies' => $this->technologies ?? [],
            'live_url' => $this->live_url,
            'github_url' => $this->github_url,
            'featured' => $this->featured,
            'sort_order' => $this->sort_order,
            'role' => $this->role,
            'client_region' => $this->client_region,
            'problem' => $this->problem,
            'solution' => $this->solution,
            'outcome' => $this->outcome,
            'confidential' => $this->confidential,
        ];
    }
}
