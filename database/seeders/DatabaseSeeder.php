<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $tags = collect([
            ['name' => 'Work', 'color' => '#8764FF', 'is_default' => true],
            ['name' => 'Personal', 'color' => '#FF7D53', 'is_default' => true],
            ['name' => 'Study', 'color' => '#2555FF', 'is_default' => true],
            ['name' => 'Fitness', 'color' => '#F478B8', 'is_default' => true],
        ])->map(function ($tag) use ($user) {
            return Tag::factory()->create(array_merge($tag, ['user_id' => $user->id]));
        });

        // Seed sample tasks matching frontend data
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Finish project proposal',
            'description' => 'Write up the final project proposal for the new client.',
            'due_date' => '2023-11-20',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
        ])->tags()->attach([$tags[0]->id]); // Work

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Buy groceries',
            'description' => 'Milk, eggs, bread, and fruits.',
            'due_date' => '2023-11-18',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::TO_DO->value,
        ])->tags()->attach([$tags[1]->id]); // Personal

        Task::factory()->completed()->create([
            'user_id' => $user->id,
            'title' => 'Review pull request',
            'description' => 'Review the latest PR from the frontend team.',
            'due_date' => '2023-11-16',
            'priority' => TaskPriority::HIGH->value,
        ])->tags()->attach([$tags[0]->id]); // Work

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Schedule dentist appointment',
            'description' => '',
            'due_date' => '2023-11-25',
            'priority' => TaskPriority::LOW->value,
            'status' => TaskStatus::TO_DO->value,
        ])->tags()->attach([$tags[1]->id]); // Personal
    }
}
