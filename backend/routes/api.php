<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\adminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

//===============================================================================================
    Route::post('/project', [ProjectController::class, 'store']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/project/{project}', [ProjectController::class, 'show']);
    Route::delete('/project/{project}', [ProjectController::class, 'destroy']);
    Route::put('/projects/{project}/complete', [ProjectController::class, 'complete']);
    Route::put('/projects/{project}/hold', [ProjectController::class, 'hold']);
    Route::post('/project/{project}/assign/{user}', [ProjectController::class, 'assignMember']);
    Route::get('/project/file/{projectFile}/download', [ProjectController::class, 'download']);
//===============================================================================================
    Route::get('/tasks', [TaskController::class, 'getAuthTask']);
    Route::get('/tasks/{project}', [TaskController::class, 'index']);
    Route::post('/task/{project}', [TaskController::class, 'store']);
    Route::put('/task/{task}/assign/{user}', [TaskController::class, 'assignTask']);
    Route::put('/task/{task}', [TaskController::class, 'update']);
    Route::delete('/task/{task}', [TaskController::class, 'destroy']);
//===============================================================================================
    Route::post('/comment/{task}', [TaskCommentController::class, 'store']);
    Route::get('/comment/{task}', [TaskCommentController::class, 'index']);
    Route::delete('/comment/{comment}', [TaskCommentController::class, 'destroy']);
//===============================================================================================
    Route::get('/projects/{project}/files', [FileController::class, 'index']);
    Route::post('/projects/{project}/files', [FileController::class, 'store']);
    Route::get('/files/{file}/download', [FileController::class, 'download']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);
//===============================================================================================
    Route::get('/file/{file}/feedback', [FeedbackController::class, 'index']);
    Route::post('/file/{file}/feedback', [FeedbackController::class, 'store']);
    Route::post('/feedback/{feedback}/simplify', [FeedbackController::class, 'simplifiedFeed']);
//===============================================================================================
    Route::get('/me' , [UserController::class, 'index']);
    Route::get('/users', [UserController::class, 'all']);
    
//===============================================================================================
    Route::get('dashboard', [DashboardController::class, 'dashboard']);
//===============================================================================================
Route::middleware('role:admin')->group(function () {
    Route::get('admin/statistic', [adminController::class, 'index']);
    Route::put('admin/{user}', [adminController::class, 'updateRole']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});
});
?>