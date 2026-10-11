<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(path: '/user', summary: 'Get the currently authenticated user',
        security: [['sanctum' => []]], tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'user', type: 'object'),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index()
    {
        return response()->json([
            "user" => auth()->user()
        ]);
    }

    #[OA\Get(path: '/users', summary: 'List all users (id, name, email, role)',
        security: [['sanctum' => []]], tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'List of users',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'users', type: 'array', items: new OA\Items(type: 'object')),
                ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function all()
    {
        return response()->json([
            'users' => User::select('id', 'name', 'email', 'role')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(path: '/user', summary: 'Create a user (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Users'],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(path: '/user/{id}', summary: 'Get a user by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Users'],
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
    #[OA\Put(path: '/user/{id}', summary: 'Update a user by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Users'],
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
    #[OA\Delete(path: '/user/{id}', summary: 'Delete a user by ID (not yet implemented)',
        security: [['sanctum' => []]], tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 501, description: 'Not implemented'),
        ],
    )]
    public function destroy(string $id)
    {
        //
    }
}