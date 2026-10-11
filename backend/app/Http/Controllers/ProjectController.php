<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    #[OA\Get(path: '/project', summary: 'List all projects', security: [['sanctum' => []]], tags: ['Projects'],
        responses: [
            new OA\Response(response: 200, description: 'List of projects with their members',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'projects', type: 'array', items: new OA\Items(type: 'object')),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index()
    {
        $user = auth()->user();

        $projects = $user->role === 'admin'
            ? Project::with('users', 'projectFiles')->get()
            : Project::with('users', 'projectFiles')
                ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
                ->get();

        return response()->json(['projects' => $projects]);
    }

    #[OA\Post(path: '/projects', summary: 'Create a new project', security: [['sanctum' => []]], tags: ['Projects'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name',        type: 'string', example: 'Website Redesign'),
                new OA\Property(property: 'description', type: 'string', example: 'Full redesign of the company website.'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Project created successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Project created successfully'),
                    new OA\Property(property: 'project', type: 'object'),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'files'       => 'nullable|array',
            'files.*'     => 'file|max:102400',
        ]);

        $project = Project::create([
            'name'        => $request->name,
            'description' => $request->description,
            'created_by'  => auth()->id(),
            'statue'      => 'planning',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $project->projectFiles()->create([
                    'path' => $file->store('project_files', 'public'),
                    'name' => $file->getClientOriginalName(),
                ]);
            }
        }

        $project->users()->attach(auth()->id(), ['role' => 'client']);

        ActivityLog::log('project.created', $project, [
            'name'        => $project->name,
            'description' => $project->description,
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load('users', 'projectFiles'),
        ], 201);
    }

    #[OA\Post(path: '/project/{project}/assign/{user}', summary: 'Assign a team member to a project',
        security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member assigned successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – admin only'),
            new OA\Response(response: 404, description: 'Project or user not found'),
        ],
    )]
    public function assignMember(Project $project, User $user)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'no']);
        }

        $project->users()->syncWithoutDetaching([$user->id => ['role' => 'team']]);
        $project->update(['statue' => 'in_progress']);

        ActivityLog::log('project.member_assigned', $project, [
            'project_name' => $project->name,
            'assigned_user_id'   => $user->id,
            'assigned_user_name' => $user->name,
        ]);

        return response()->json(['message' => "{$user->name} Been Assigned To {$project->name}"]);
    }

    #[OA\Get(path: '/project/{project}', summary: 'Get a single project', security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Project details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function show(Project $project)
    {
        return response()->json(['project' => $project->load(['users'])]);
    }

    #[OA\Get(path: '/projectfile/{projectFile}/download', summary: 'Download a project file (attached at project creation)',
        security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [new OA\Parameter(name: 'projectFile', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'File download stream'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Project file not found'),
        ],
    )]
    public function download(ProjectFile $projectFile)
    {
        return Storage::disk('public')->download($projectFile->path, $projectFile->name);
    }

    #[OA\Put(path: '/project/{project}/complete', summary: 'Mark a project as completed',
        security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Project completed successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot complete projects'),
            new OA\Response(response: 422, description: 'Unfinished tasks remain'),
        ],
    )]
    public function complete(Project $project)
    {
        if (auth()->user()->role === 'client') {
            return response()->json(['message' => 'no']);
        }

        $unfinished = $project->tasks()->where('status', '!=', 'completed')->count();

        if ($unfinished > 0) {
            return response()->json(['message' => "there is still {$unfinished} tasks left to do"], 422);
        }

        $project->update(['statue' => 'completed']);

        ActivityLog::log('project.completed', $project, ['name' => $project->name]);

        return response()->json([
            'message' => "{$project->name} Project completed successfully",
            'project' => $project->load('tasks'),
        ]);
    }

    #[OA\Put(path: '/project/{project}/hold', summary: 'Put a project on hold',
        security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Project put on hold'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot put projects on hold'),
        ],
    )]
    public function hold(Project $project)
    {
        if (auth()->user()->role === 'client') {
            return response()->json(['message' => 'no']);
        }

        if ($project->statue === 'on_hold') {
            $hasTeam = $project->users()->wherePivot('role', 'team')->exists();
            $newStatus = $hasTeam ? 'in_progress' : 'planning';
            $project->update(['statue' => $newStatus]);

            ActivityLog::log('project.unhold', $project, [
                'name'       => $project->name,
                'new_status' => $newStatus,
            ]);

            return response()->json(['message' => 'done']);
        }

        $project->update(['statue' => 'on_hold']);

        ActivityLog::log('project.on_hold', $project, ['name' => $project->name]);

        return response()->json(['message' => "{$project->name} Project Was Put On Hold"]);
    }

    #[OA\Put(path: '/project/{id}', summary: 'Update project details (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function update(Request $request, string $id)
    {
        //
    }

    #[OA\Delete(path: '/project/{project}', summary: 'Delete a project',
        security: [['sanctum' => []]], tags: ['Projects'],
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Project deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – team members cannot delete, only the creator or an admin can'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function destroy(Project $project)
    {
        if (auth()->user()->role === 'team') {
            return response()->json(['message' => 'no']);
        }

        if (auth()->id() !== $project->created_by && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'no']);
        }

        ActivityLog::log('project.deleted', null, [
            'project_id'   => $project->id,
            'project_name' => $project->name,
        ]);

        $project->delete();

        return response()->json(['message' => 'deleted']);
    }
}