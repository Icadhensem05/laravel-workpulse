<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TasksController extends Controller
{
    public function board(): JsonResponse
    {
        $tasks = Task::query()->with(['creator', 'assignee'])->orderBy('status')->orderBy('sort_order')->latest('id')->get();
        $statuses = ['todo', 'in_progress', 'done'];

        return response()->json([
            'success' => true,
            'columns' => collect($statuses)->map(function ($status) use ($tasks) {
                return [
                    'id' => $status,
                    'title' => str_replace('_', ' ', ucfirst($status)),
                    'tasks' => $tasks->where('status', $status)->values()->map(fn (Task $task) => $this->transformTask($task))->values(),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:todo,in_progress,done'],
        ])->validate();

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'status' => $data['status'] ?? 'todo',
            'created_by_user_id' => $request->user()->id,
            'sort_order' => (Task::query()->where('status', $data['status'] ?? 'todo')->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'task' => $this->transformTask($task->fresh(['creator', 'assignee'])),
        ], 201);
    }

    public function move(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'status' => ['required', 'string', 'in:todo,in_progress,done'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $task = Task::query()->findOrFail($data['task_id']);
        $task->status = $data['status'];
        $task->sort_order = $data['sort_order'] ?? ((Task::query()->where('status', $data['status'])->max('sort_order') ?? 0) + 1);
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Task moved successfully.',
            'task' => $this->transformTask($task->fresh(['creator', 'assignee'])),
        ]);
    }

    private function transformTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => optional($task->due_date)->toDateString(),
            'created_by' => $task->creator?->display_name,
            'assigned_to' => $task->assignee?->display_name,
            'sort_order' => (int) $task->sort_order,
        ];
    }
}
