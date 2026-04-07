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

        $project = Project::query()->create($request->validated());

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

        $project->update($request->validated());

        return new ProjectResource($project->refresh());
    }

    public function destroy(Project $project): Response
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
