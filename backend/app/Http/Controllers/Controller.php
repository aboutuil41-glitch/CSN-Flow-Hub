<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'CSN API',
    description: 'API for managing Projects, Tasks, Clients with an AI integration For Simplified Clients Feedback.',
    version: '1.0.0',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
#[OA\Server(
    url: '/api',
    description: 'API Server',
)]
abstract class Controller{}

