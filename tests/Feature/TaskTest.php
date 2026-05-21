<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_own_tasks(): void
    {
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);
        Task::factory()->count(2)->create(); // other user's tasks

        $response = $this->actingAs($this->user)->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_task_with_tags(): void
    {
        $tag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Work']);

        $response = $this->actingAs($this->user)->postJson('/api/tasks', [
            'title' => 'New Task',
            'due_date' => '2023-12-01',
            'priority' => 'high',
            'tags' => ['Work'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'New Task')
            ->assertJsonCount(1, 'tags');
            
        $this->assertDatabaseHas('tasks', ['title' => 'New Task']);
        $this->assertDatabaseHas('task_tag', ['tag_id' => $tag->id]);
    }

    public function test_create_task_validates_title(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/tasks', [
            'due_date' => '2023-12-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_show_own_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('id', (string) $task->id);
    }

    public function test_user_cannot_show_others_task(): void
    {
        $task = Task::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/tasks/{$task->id}");

        $response->assertForbidden();
    }

    public function test_user_can_update_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Updated Title');
            
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Title']);
    }

    public function test_toggle_todo_to_inprogress(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::TO_DO->value
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('status', TaskStatus::IN_PROGRESS->value);
    }

    public function test_toggle_inprogress_to_done_sets_completed_at(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::IN_PROGRESS->value
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('status', TaskStatus::DONE->value);
            
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_toggle_done_to_todo_clears_completed_at(): void
    {
        $task = Task::factory()->completed()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('status', TaskStatus::TO_DO->value);
            
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_due_today_filter(): void
    {
        Task::factory()->dueToday()->create(['user_id' => $this->user->id]);
        Task::factory()->dueToday()->completed()->create(['user_id' => $this->user->id]); // Should not be returned
        Task::factory()->create(['user_id' => $this->user->id, 'due_date' => today()->addDay()->format('Y-m-d')]); // Not today

        $response = $this->actingAs($this->user)->getJson('/api/tasks/due-today');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_delete_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $task->tags()->attach($tag);

        $response = $this->actingAs($this->user)->deleteJson("/api/tasks/{$task->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('task_tag', ['task_id' => $task->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]); // Tag itself should remain
    }
}
