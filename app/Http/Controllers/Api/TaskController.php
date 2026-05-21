<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request): TaskCollection
    {
        $query = $request->user()->tasks()->with('tags');

        // Sorting
        $sortBy = $request->query('sort_by', 'due_date');
        $sortDirection = $request->query('sort_direction', 'asc');
        
        $allowedSorts = ['due_date', 'title', 'priority'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        // Filtering
        if ($status = $request->query('status')) {
            if ($status === 'todo') $status = 'to-do';
            if ($status === 'inprogress') $status = 'in-progress';
            if ($status === 'completed') $status = 'done';
            $query->where('status', $status);
        }

        if ($tagName = $request->query('tag')) {
            $query->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            });
        }

        return new TaskCollection($query->get());
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $task = $request->user()->tasks()->create($validated);

        if (isset($validated['tags'])) {
            $tagIds = $request->user()->tags()->whereIn('name', $validated['tags'])->pluck('id');
            $task->tags()->sync($tagIds);
        }

        $task->load('tags');

        return response()->json(new TaskResource($task), 201);
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);
        
        return new TaskResource($task->load('tags'));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $validated = $request->validated();
        
        if (isset($validated['status'])) {
            if ($validated['status'] === TaskStatus::DONE->value && $task->status->value !== TaskStatus::DONE->value) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== TaskStatus::DONE->value) {
                $validated['completed_at'] = null;
            }
        }

        $task->update($validated);

        if (array_key_exists('tags', $validated)) {
            $tagIds = $request->user()->tags()->whereIn('name', $validated['tags'])->pluck('id');
            $task->tags()->sync($tagIds);
        }

        return new TaskResource($task->load('tags'));
    }

    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);
        
        $task->delete();

        return response()->noContent();
    }

    public function toggle(Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $statusCycle = [
            TaskStatus::TO_DO->value => TaskStatus::IN_PROGRESS->value,
            TaskStatus::IN_PROGRESS->value => TaskStatus::DONE->value,
            TaskStatus::DONE->value => TaskStatus::TO_DO->value,
        ];

        $newStatus = $statusCycle[$task->status->value];
        $completedAt = $newStatus === TaskStatus::DONE->value ? now() : null;

        $task->update([
            'status' => $newStatus,
            'completed_at' => $completedAt,
        ]);

        return new TaskResource($task->load('tags'));
    }

    public function dueToday(Request $request): TaskCollection
    {
        $tasks = $request->user()->tasks()
            ->with('tags')
            ->dueToday()
            ->where('status', '!=', TaskStatus::DONE->value)
            ->get();

        return new TaskCollection($tasks);
    }
}
