<?php

namespace App\Http\Controllers\Startup;

use App\Events\Startup\StartupCreated;
use App\Http\Controllers\Controller;
use App\Http\Resources\Startup\StartupPublicResource;
use App\Models\Startup\Startup;
use App\Models\Startup\TeamMember;
use App\Services\Startup\StartupAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Startup directory + ownership (Wave 5A / D-061). Public directory is public-projection only
 * (C-1/M-1). FOUNDER-OWNED (H-3): create stamps founder_id; access via StartupAccessService
 * (participation family) — NOT AccountScope. Creation fires a ONE-WAY CRM lead (D-053).
 */
class StartupController extends Controller
{
    public function __construct(private readonly StartupAccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        // Public directory: active startups only; public projection.
        $startups = Startup::query()->where('status', 'active')
            ->when($request->filled('industry'), fn ($q) => $q->where('industry', $request->string('industry')))
            ->when($request->filled('stage'), fn ($q) => $q->where('lifecycle_stage', $request->string('stage')))
            ->paginate(15);

        return response()->json(StartupPublicResource::collection($startups)->response()->getData(true));
    }

    public function show(Startup $startup): JsonResponse
    {
        abort_unless($startup->status === 'active', 404);

        return response()->json(new StartupPublicResource($startup));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('startup.profiles.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:100'],
            'founding_year' => ['nullable', 'integer', 'min:1900'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $startup = Startup::create($data + [
            'founder_id' => $request->user()->id, // H-3 founder-owned
            'slug' => Str::slug($data['name']).'-'.Str::random(6),
            'lifecycle_stage' => 'idea',
            'status' => 'active',
        ]);

        // The creating founder is the first active founder member.
        TeamMember::create([
            'startup_id' => $startup->id,
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'role' => 'founder',
            'is_founder' => true,
            'status' => 'active',
        ]);

        event(new StartupCreated($startup)); // one-way → CRM (D-053)

        return response()->json(['id' => $startup->id, 'slug' => $startup->slug], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json(
            Startup::query()->where('founder_id', $request->user()->id)
                ->select(['id', 'name', 'slug', 'lifecycle_stage', 'status', 'is_verified'])->paginate(25)
        );
    }

    public function update(Request $request, Startup $startup): JsonResponse
    {
        abort_unless($this->access->canManage($request->user(), $startup), 403);

        $startup->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'team_size' => ['nullable', 'integer', 'min:1'],
            'stage' => ['sometimes', 'in:idea,mvp,growth,scale,exit'],
        ]));

        return response()->json(['message' => __('Startup updated.')]);
    }
}
