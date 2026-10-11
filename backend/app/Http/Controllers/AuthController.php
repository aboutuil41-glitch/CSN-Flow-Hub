<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(path: '/register', summary: 'Register a new user', tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name','email','password','password_confirmation'],
            properties: [
                new OA\Property(property: 'name',                  type: 'string',  example: 'John Doe'),
                new OA\Property(property: 'email',                 type: 'string',  format: 'email',    example: 'john@example.com'),
                new OA\Property(property: 'password',              type: 'string',  format: 'password', example: 'password123'),
                new OA\Property(property: 'password_confirmation', type: 'string',  format: 'password', example: 'password123'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'User registered successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'user',  type: 'object'),
                    new OA\Property(property: 'token', type: 'string'),
                ])),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user  = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        ActivityLog::create([
            'user_id'      => $user->id,
            'action'       => 'user.registered',
            'subject_type' => 'User',
            'subject_id'   => $user->id,
            'metadata'     => ['name' => $user->name, 'email' => $user->email],
        ]);

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    #[OA\Post(path: '/login', summary: 'Login and get a bearer token', tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email','password'],
            properties: [
                new OA\Property(property: 'email',    type: 'string', format: 'email',    example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Login successful',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'user',  type: 'object'),
                    new OA\Property(property: 'token', type: 'string'),
                ])),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ],
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        ActivityLog::log('user.login', $user, ['email' => $user->email]);

        return response()->json(['user' => $user, 'token' => $token]);
    }

    #[OA\Post(path: '/logout', summary: 'Logout and revoke token', security: [['sanctum' => []]], tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Logged out')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function logout(Request $request)
    {
        ActivityLog::log('user.logout');

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}