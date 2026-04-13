<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('projects', 'slug')],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'technologies' => ['required', 'array', 'min:1'],
            'technologies.*' => ['required', 'string', 'max:100', 'distinct'],
            'live_url' => ['nullable', 'url', 'max:2048'],
            'github_url' => ['nullable', 'url', 'max:2048'],
            'featured' => ['required', 'boolean'],
            'published' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'role' => ['nullable', 'string', 'max:255'],
            'client_region' => ['nullable', 'string', 'max:255'],
            'problem' => ['nullable', 'string'],
            'solution' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
            'confidential' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $technologies = $this->input('technologies');

        $this->merge([
            'slug' => Str::slug((string) $this->input('slug', $this->input('title'))),
            'image_url' => trim((string) $this->input('image_url')) ?: null,
            'technologies' => is_array($technologies)
                ? array_values(array_filter(array_map(
                    static fn (mixed $technology): string => trim((string) $technology),
                    $technologies,
                ), static fn (string $technology): bool => $technology !== ''))
                : $technologies,
        ]);
    }
}
