<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ActivityLogController extends Controller
{

    #[OA\Get(path: '/logs', summary: 'List activity logs (filterable, paginated)',
        security: [['sanctum' => []]], tags: ['Activity Logs'],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: "Filter by action name, e.g. 'task.created'"),
            new OA\Parameter(name: 'subject_type', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: "Filter by subject model type, e.g. 'Task'"),
            new OA\Parameter(name: 'subject_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filter by subject model ID'),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filter by the user who performed the action'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Results per page'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of activity logs',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'logs', type: 'object', description: 'Laravel paginator object'),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – admin only'),
        ],
    )]
    public function index(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = ActivityLog::with('user:id,name,email')
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json([
            'logs' => $query->paginate($request->integer('per_page', 20))
        ]);
    }
}