<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasksModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_move_task(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $taskId = $this->actingAs($user)
            ->postJson('/app-api/tasks', [
                'title' => 'Prepare report',
                'description' => 'Compile monthly report',
                'priority' => 'high',
                'status' => 'todo',
            ])
            ->assertCreated()
            ->json('task.id');

        $this->actingAs($user)
            ->postJson('/app-api/tasks/move', [
                'task_id' => $taskId,
                'status' => 'in_progress',
                'sort_order' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('task.status', 'in_progress');

        $this->actingAs($user)
            ->getJson('/app-api/tasks/board')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
