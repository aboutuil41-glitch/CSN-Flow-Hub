<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TaskCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/comment/{task}',
        summary: 'List all comments on a task',
        security: [['sanctum' => []]],
        tags: ['Task Comments'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task with its comments',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'comments', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Task not found'),
        ],
    )]
    public function index(Task $task)
    {
        return response()->json([
            'comments' => $task->load(['comments']) 
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/comment/{task}',
        summary: 'Add a comment to a task',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 255, example: 'Working on this now.'),
                ],
            ),
        ),
        tags: ['Task Comments'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Task ID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comment sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'comment sent successfully'),
                        new OA\Property(property: 'comment', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – clients cannot comment on tasks'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(Request $request, Task $task)
    {
        if(auth()->user()->role === 'client'){
            return response()->json([
                'message' => "no",
        ]);
        }


        $request->validate([
            'content' => 'required|max:255'
        ]);

        $comment = TaskComment::updateOrCreate([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $request->content
        ]);

        return response()->json([
            'message' => 'comment sent successfully',
            'comment' => $comment
        ]);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(path: '/comment/single/{id}', summary: 'Get a single comment by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Task Comments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(path: '/comment/{id}', summary: 'Update a comment by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Task Comments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/comment/{comment}',
        summary: 'Delete a task comment',
        security: [['sanctum' => []]],
        tags: ['Task Comments'],
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Comment ID'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comment deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'message deleted successfully'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – only the comment owner or an admin can delete'),
            new OA\Response(response: 404, description: 'Comment not found'),
        ],
    )]
    public function destroy(TaskComment $comment)
    {

        if(auth()->user()->id !== $comment->user->id || auth()->user()->role !== 'admin'){
            return response()->json([
                'message' => "no",
        ]);
        }
        $comment->delete();

        return response()->json([
            'message' => 'message deleted successfully'
        ]);
    }
}