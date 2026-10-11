<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(path: '/dashboard', summary: "Get the authenticated user's dashboard overview",
        security: [['sanctum' => []]], tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Up to 4 active projects and 4 incomplete assigned tasks',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'projects', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'my_tasks', type: 'array', items: new OA\Items(type: 'object')),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function dashboard()
    {
        $user = Auth::user();

        $projects = Project::where('created_by', $user->id)
            ->orWhereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->where('statue', '!=', 'completed')
            ->withCount('tasks')
            ->with(['users' => fn($q) => $q->select('users.id', 'name', 'email')])
            ->latest()
            ->take(4)
            ->get();

        $myTasks = Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->with('project:id,name')
            ->latest()
            ->take(4)
            ->get();

        return response()->json([
            'projects' => $projects,
            'my_tasks' => $myTasks,
        ]);
    }
}