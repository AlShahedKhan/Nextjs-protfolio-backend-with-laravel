<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $validated = $request->validate([
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'published' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $projects = Project::query()
            ->when(
                isset($validated['search']),
                function ($query) use ($validated) {
                    $search = trim((string) $validated['search']);

                    $query->where(function ($nestedQuery) use ($search): void {
                        $nestedQuery
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('role', 'like', "%{$search}%")
                            ->orWhere('client_region', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                isset($validated['featured']),
                fn ($query) => $query->where('featured', filter_var($validated['featured'], FILTER_VALIDATE_BOOL)),
            )
            ->when(
                isset($validated['published']),
                fn ($query) => $query->where('published', filter_var($validated['published'], FILTER_VALIDATE_BOOL)),
            )
            ->orderBy('sort_order')
            ->orderByDesc('featured')
            ->orderBy('title')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $attributes = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $attributes['image_url'] = $this->storeUploadedImage($request->file('image'));
        }

        $project = Project::query()->create($attributes);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $attributes = $request->safe()->except(['image', 'remove_image']);

        if (($request->boolean('remove_image') || $request->has('image_url')) && ! $request->hasFile('image')) {
            $this->deleteStoredImageFromUrl($project->image_url);

            if ($request->boolean('remove_image')) {
                $attributes['image_url'] = null;
            }
        }

        if ($request->hasFile('image')) {
            $this->deleteStoredImageFromUrl($project->image_url);
            $attributes['image_url'] = $this->storeUploadedImage($request->file('image'));
        }

        $project->update($attributes);

        return new ProjectResource($project->refresh());
    }

    public function destroy(Project $project): Response
    {
        $this->authorize('delete', $project);

        $this->deleteStoredImageFromUrl($project->image_url);
        $project->delete();

        return response()->noContent();
    }

    private function storeUploadedImage(UploadedFile $file): string
    {
        $path = $file->store('projects', 'public');

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredImageFromUrl(?string $url): void
    {
        if (! is_string($url) || trim($url) === '') {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return;
        }

        $diskPath = ltrim(substr($path, strlen('/storage/')), '/');

        if ($diskPath !== '') {
            Storage::disk('public')->delete($diskPath);
        }
    }
}
