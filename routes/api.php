<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\TaskController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/tasks/due-today', [TaskController::class, 'dueToday']);
    Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggle']);
    Route::apiResource('tasks', TaskController::class);

    Route::apiResource('tags', App\Http\Controllers\Api\TagController::class)->except(['show']);
});
