<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TaskController extends Controller
{
    #[OA\Get(path: '/my-tasks', summary: 'List tasks assigned to the authenticated user',
        security: [['sanctum' => []]], tags: ['Tasks'],
        responses: [
            new OA\Response(response: 200, description: 'List of tasks assigned to the caller',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(type: 'object')),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function getAuthTask()
    {
        $tasks = Task::where('assigned_to', auth()->id())->with(['assignee'])->get();
        return response()->json(['tasks' => $tasks]);
    }

    #[OA\Get(path: '/tasks/{project}', summary: 'List all tasks for a project',
        security: [['sanctum' => []]], tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'List of tasks',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(type: 'object')),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function index(Project $project)
    {
        $tasks = $project->tasks()->with(['assignee'])->get();
        return response()->json(['tasks' => $tasks]);
    }

    #[OA\Post(path: '/tasks/{project}', summary: 'Create a task for a project',
        security: [['sanctum' => []]], tags: ['Tasks'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title',       type: 'string', example: 'Design homepage mockup'),
                new OA\Property(property: 'description', type: 'string', example: 'Create wireframes for all breakpoints.'),
                new OA\Property(property: 'deadline',    type: 'string', format: 'date', example: '2025-12-31'),
            ],
        )),
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Task created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot create tasks'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(Request $request, Project $project)
    {
        if (auth()->user()->role === 'client') {
            return response()->json(['message' => 'no']);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
        ]);

        $task = Task::create([
            'title'       => $request->title,
            'description' => $request->description,
            'project_id'  => $project->id,
            'deadline'    => $request->deadline,
        ]);

        ActivityLog::log('task.created', $task, [
            'title'        => $task->title,
            'project_id'   => $project->id,
            'project_name' => $project->name,
        ]);

        return response()->json(['task' => $task]);
    }

    #[OA\Post(path: '/task/{task}/assign/{user}', summary: 'Assign a task to a user',
        security: [['sanctum' => []]], tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task assigned successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot assign tasks'),
            new OA\Response(response: 409, description: 'Task already assigned'),
        ],
    )]
    public function assignTask(Task $task, User $user)
    {
        if (auth()->user()->role === 'client') {
            return response()->json(['message' => 'no']);
        }

        if ($task->assigned_to !== null) {
            return response()->json(['message' => "{$task->title} had been already assigned"]);
        }

        $task->update(['assigned_to' => $user->id]);

        ActivityLog::log('task.assigned', $task, [
            'title'              => $task->title,
            'assigned_user_id'   => $user->id,
            'assigned_user_name' => $user->name,
        ]);

        return response()->json(['message' => "You Have Assigned {$user->name} {$task->title}"]);
    }

    #[OA\Get(path: '/task/{id}', summary: 'Get a task by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function show(string $id)
    {
        //
    }

    #[OA\Put(path: '/task/{task}', summary: "Update a task's status (assigned user only)",
        security: [['sanctum' => []]], tags: ['Tasks'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['pending','in_progress','completed','blocked'], example: 'in_progress'),
            ],
        )),
        parameters: [new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Task updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – only the assigned user can update this task'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(Request $request, Task $task)
    {
        if ($task->assigned_to !== auth()->id()) {
            return response()->json(['message' => "You can't update this task"], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,blocked',
        ]);

        $oldStatus = $task->status;
        $task->update(['status' => $request->status]);

        ActivityLog::log('task.status_updated', $task, [
            'title'      => $task->title,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
        ]);

        return response()->json(['message' => 'Task updated', 'task' => $task]);
    }

    #[OA\Delete(path: '/task/{task}', summary: 'Delete a task',
        security: [['sanctum' => []]], tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Task deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – admin only'),
            new OA\Response(response: 404, description: 'Task not found'),
        ],
    )]
    public function destroy(Task $task)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'no']);
        }

        ActivityLog::log('task.deleted', null, [
            'task_id'    => $task->id,
            'task_title' => $task->title,
            'project_id' => $task->project_id,
        ]);

        $task->delete();

        return response()->json(['message' => "{$task->title} Has Been Deleted Successfully"]);
    }
}