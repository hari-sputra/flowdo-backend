<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return TagResource::collection($request->user()->tags()->get());
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_default'] = false; // Force false

        $tag = $request->user()->tags()->create($validated);

        return response()->json(new TagResource($tag), 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        Gate::authorize('update', $tag);

        $validated = $request->validated();
        $tag->update($validated);

        return new TagResource($tag);
    }

    public function destroy(Tag $tag): Response
    {
        Gate::authorize('delete', $tag);

        $tag->delete();

        return response()->noContent();
    }
}
