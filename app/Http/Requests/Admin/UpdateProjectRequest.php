<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('projects', 'slug')->ignore($this->route('project')),
            ],
            'description' => ['sometimes', 'string'],
            'image_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'technologies' => ['sometimes', 'array', 'min:1'],
            'technologies.*' => ['required_with:technologies', 'string', 'max:100', 'distinct'],
            'live_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'github_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'featured' => ['sometimes', 'boolean'],
            'published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'client_region' => ['sometimes', 'nullable', 'string', 'max:255'],
            'problem' => ['sometimes', 'nullable', 'string'],
            'solution' => ['sometimes', 'nullable', 'string'],
            'outcome' => ['sometimes', 'nullable', 'string'],
            'confidential' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];
        $technologies = $this->input('technologies');

        if ($this->has('slug')) {
            $updates['slug'] = Str::slug((string) $this->input('slug'));
        }

        if (is_array($technologies)) {
            $updates['technologies'] = array_values(array_filter(array_map(
                static fn (mixed $technology): string => trim((string) $technology),
                $technologies,
            ), static fn (string $technology): bool => $technology !== ''));
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
