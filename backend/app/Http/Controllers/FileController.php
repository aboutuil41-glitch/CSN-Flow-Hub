<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\File;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class FileController extends Controller
{
    #[OA\Get(path: '/projects/{project}/files', summary: 'List all files for a project',
        security: [['sanctum' => []]], tags: ['Files'],
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Project with its files'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function index(Project $project)
    {
        return response()->json(['files' => $project->load('files')]);
    }

    #[OA\Post(path: '/projects/{project}/files', summary: 'Upload a file to a project',
        security: [['sanctum' => []]], tags: ['Files'],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(required: ['file'], properties: [
                new OA\Property(property: 'file',    type: 'string', format: 'binary'),
                new OA\Property(property: 'version', type: 'string', example: 'v1.0'),
            ]),
        )),
        parameters: [new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'File uploaded successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot upload files'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(Request $request, Project $project)
    {
        if (auth()->user()->role === 'client') {
            return response()->json(['message' => 'no']);
        }

        $request->validate(['file' => 'required|file|max:102400']);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('project_files', 'public');

        $file = File::create([
            'project_id'  => $project->id,
            'uploaded_by' => auth()->id(),
            'name'        => $uploadedFile->getClientOriginalName(),
            'path'        => $path,
            'version'     => $request->version,
        ]);

        ActivityLog::log('file.uploaded', $file, [
            'file_name'    => $file->name,
            'version'      => $file->version,
            'project_id'   => $project->id,
            'project_name' => $project->name,
        ]);

        return response()->json(['message' => 'File uploaded successfully', 'file' => $file], 201);
    }

    #[OA\Get(path: '/files/{file}/download', summary: 'Download a file',
        security: [['sanctum' => []]], tags: ['Files'],
        parameters: [new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'File download stream'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'File not found'),
        ],
    )]
    public function download(File $file)
    {
        ActivityLog::log('file.downloaded', $file, ['file_name' => $file->name]);

        return Storage::disk('public')->download($file->path, $file->name);
    }

    #[OA\Get(path: '/files/{id}', summary: 'Get a file by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Files'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function show(string $id) {}

    #[OA\Put(path: '/files/{id}', summary: 'Update a file by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Files'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function update(Request $request, string $id) {}

    #[OA\Delete(path: '/files/{file}', summary: 'Delete a file',
        security: [['sanctum' => []]], tags: ['Files'],
        parameters: [new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'File deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot delete files'),
            new OA\Response(response: 404, description: 'File not found'),
        ],
    )]
    public function destroy(File $file)
    {
        if (auth()->user()->role === 'client') {
            return response()->json(['message' => 'no']);
        }

        ActivityLog::log('file.deleted', null, [
            'file_id'    => $file->id,
            'file_name'  => $file->name,
            'project_id' => $file->project_id,
        ]);

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}