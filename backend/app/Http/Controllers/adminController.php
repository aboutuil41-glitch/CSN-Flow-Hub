<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Feedback;
use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class adminController extends Controller
{
    #[OA\Get(path: '/admin/statistic', summary: 'Get dashboard statistics',
        security: [['sanctum' => []]], tags: ['Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard statistics'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index()
    {
        return response()->json([
            'users' => [
                'total'        => User::count(),
                'clients'      => User::where('role', 'client')->count(),
                'team_members' => User::where('role', 'team')->count(),
                'admins'       => User::where('role', 'admin')->count(),
            ],
            'projects' => [
                'total'       => Project::count(),
                'planning'    => Project::where('statue', 'planning')->count(),
                'in_progress' => Project::where('statue', 'in_progress')->count(),
                'completed'   => Project::where('statue', 'completed')->count(),
                'on_hold'     => Project::where('statue', 'on_hold')->count(),
            ],
            'tasks' => [
                'total'       => Task::count(),
                'pending'     => Task::where('status', 'pending')->count(),
                'in_progress' => Task::where('status', 'in_progress')->count(),
                'completed'   => Task::where('status', 'completed')->count(),
                'blocked'     => Task::where('status', 'blocked')->count(),
            ],
            'files' => [
                'total' => File::count(),
            ],
            'feedback' => [
                'total'           => Feedback::count(),
                'comments'        => Feedback::where('type', 'comment')->count(),
                'approvals'       => Feedback::where('type', 'approval')->count(),
                'change_requests' => Feedback::where('type', 'change_request')->count(),
            ],
        ]);
    }

    #[OA\Post(path: '/admin', summary: 'Create an admin-managed resource (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Admin'],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function store(Request $request) {}

    #[OA\Get(path: '/admin/{id}', summary: 'Get an admin-managed resource by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Admin'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function show(string $id) {}

    #[OA\Put(path: '/admin/{user}', summary: "Update a user's role",
        security: [['sanctum' => []]], tags: ['Admin'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['role'],
            properties: [new OA\Property(property: 'role', type: 'string', enum: ['client','team','admin'], example: 'team')],
        )),
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Role updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – admin only'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function updateRole(Request $request, User $user)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'no']);
        }

        $request->validate(['role' => 'required|in:client,team,admin']);

        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        ActivityLog::log('admin.role_updated', $user, [
            'user_name' => $user->name,
            'old_role'  => $oldRole,
            'new_role'  => $request->role,
        ]);

        return response()->json(['message' => "{$user->name} Role Has Been Updated To {$request->role}"]);
    }

    #[OA\Delete(path: '/admin/{id}', summary: 'Delete an admin-managed resource by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Admin'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function destroy(string $id) {}
}