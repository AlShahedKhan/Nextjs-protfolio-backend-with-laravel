<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $projects = Project::query()
            ->published()
            ->when(
                isset($validated['featured']),
                fn (Builder $query) => $query->where('featured', filter_var($validated['featured'], FILTER_VALIDATE_BOOL)),
            )
            ->when(
                isset($validated['search']),
                function (Builder $query) use ($validated): void {
                    $search = trim((string) $validated['search']);

                    $query->where(function (Builder $nestedQuery) use ($search): void {
                        $nestedQuery
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('role', 'like', "%{$search}%")
                            ->orWhere('client_region', 'like', "%{$search}%");
                    });
                },
            )
            ->ordered()
            ->paginate($validated['per_page'] ?? 12)
            ->withQueryString();

        return PublicProjectResource::collection($projects);
    }

    public function show(string $slug): PublicProjectResource
    {
        $project = Project::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return new PublicProjectResource($project);
    }
}
