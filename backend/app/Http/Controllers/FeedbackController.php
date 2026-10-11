<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AiSimplification;
use App\Models\Feedback;
use App\Models\File;
use App\Services\AiService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FeedbackController extends Controller
{
    #[OA\Get(path: '/file/{file}/feedback', summary: 'List all feedback for a file',
        security: [['sanctum' => []]], tags: ['Feedback'],
        parameters: [new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'File with its feedback'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'File not found'),
        ],
    )]
    public function index(File $file)
    {
        return response()->json(['feedback' => $file->feedback()->with('user')->get()]);
    }

    #[OA\Post(path: '/file/{file}/feedback', summary: 'Submit feedback on a file',
        security: [['sanctum' => []]], tags: ['Feedback'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['message','type'],
            properties: [
                new OA\Property(property: 'message', type: 'string',  example: 'Please revise the color scheme.'),
                new OA\Property(property: 'type',    type: 'string',  enum: ['comment','approval','change_request','blocked'], example: 'change_request'),
            ],
        )),
        parameters: [new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Feedback submitted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(Request $request, File $file)
    {
        $request->validate([
            'message' => 'required|string',
            'type'    => 'required|in:comment,approval,change_request,blocked',
        ]);

        $feedback = Feedback::updateOrCreate(
            ['user_id' => auth()->id(), 'file_id' => $file->id],
            ['message' => $request->message, 'type' => $request->type]
        );

        ActivityLog::log('feedback.submitted', $feedback, [
            'type'      => $feedback->type,
            'file_id'   => $file->id,
            'file_name' => $file->name,
        ]);

        return response()->json(['message' => "your {$feedback->type} feedback has been sent."]);
    }

    #[OA\Get(path: '/feedback/{feedback}/simplify', summary: 'Get an AI-simplified summary of a feedback entry',
        security: [['sanctum' => []]], tags: ['Feedback'],
        parameters: [new OA\Parameter(name: 'feedback', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'AI-generated simplified feedback'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Feedback not found'),
        ],
    )]
    public function simplifiedFeed(AiService $aiService, Feedback $feedback)
    {
        $result = $aiService->analyzeFeedBack($feedback->toArray());

        $response = AiSimplification::updateOrCreate(
            ['feedback_id' => $feedback->id],
            [
                'simplified_feedback' => $result['simplified_feedback'],
                'sentiment'           => $result['sentiment'],
                'priority'            => $result['priority'],
                'suggested_action'    => $result['suggested_action'],
            ]
        );

        ActivityLog::log('feedback.ai_simplified', $feedback, [
            'feedback_id' => $feedback->id,
            'sentiment'   => $result['sentiment'],
            'priority'    => $result['priority'],
        ]);

        return response()->json(['message' => $response]);
    }

    #[OA\Get(path: '/feedback/{id}', summary: 'Get a feedback entry by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Feedback'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function show(string $id) {}

    #[OA\Put(path: '/feedback/{id}', summary: 'Update a feedback entry by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Feedback'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function update(Request $request, string $id) {}

    #[OA\Delete(path: '/feedback/{id}', summary: 'Delete a feedback entry by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Feedback'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function destroy(string $id) {}
}