<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_tags(): void
    {
        Tag::factory()->count(2)->create(['user_id' => $this->user->id]);
        Tag::factory()->count(3)->create(); // other user's tags

        $response = $this->actingAs($this->user)->getJson('/api/tags');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    public function test_user_can_create_custom_tag(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/tags', [
            'name' => 'Custom Tag',
            'color' => '#123456',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Custom Tag')
            ->assertJsonPath('isDefault', false);
            
        $this->assertDatabaseHas('tags', ['name' => 'Custom Tag', 'is_default' => false]);
    }

    public function test_create_tag_validates_duplicate_name(): void
    {
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Existing']);

        $response = $this->actingAs($this->user)->postJson('/api/tags', [
            'name' => 'Existing',
            'color' => '#ffffff',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_cannot_update_default_tag(): void
    {
        $tag = Tag::factory()->default()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/tags/{$tag->id}", [
            'name' => 'New Name',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_update_custom_tag(): void
    {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/api/tags/{$tag->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name');
    }

    public function test_user_cannot_delete_default_tag(): void
    {
        $tag = Tag::factory()->default()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/tags/{$tag->id}");

        $response->assertForbidden();
    }

    public function test_user_can_delete_custom_tag(): void
    {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $task->tags()->attach($tag);

        $response = $this->actingAs($this->user)->deleteJson("/api/tags/{$tag->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('task_tag', ['tag_id' => $tag->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]); // Task itself should remain
    }
}
