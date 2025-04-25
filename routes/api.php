<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\ProjectController;
use App\Http\Controllers\API\V1\SubTaskController;
use App\Http\Controllers\API\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::put('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);
        Route::patch('/{id}/assign', [ProjectController::class, 'assignMember']);
        Route::patch('/{id}/unassign', [ProjectController::class, 'unassignMember']);
    });

    Route::prefix('tasks')->group(function () {
        Route::get('/project/{id}', [TaskController::class, 'findByProject']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
        Route::patch('/{id}/complete', [TaskController::class, 'markAsComplete']);
        Route::patch('/{id}/assign', [TaskController::class, 'assign']);
        Route::patch('/{id}/unassign', [TaskController::class, 'unassign']);

        Route::get('/{id}/subtasks', [SubTaskController::class, 'findByTask']);
        Route::post('/{id}/subtasks', [SubTaskController::class, 'storeSubtask']);
    });

    Route::apiResource('subtasks', SubTaskController::class)->except(['store', 'index']);

    Route::get('/subtasks/task/{taskId}', [SubTaskController::class, 'findByTask']);
    Route::patch('/subtasks/{id}/complete', [SubTaskController::class, 'markAsComplete']);

    Route::patch('/subtasks/{id}/assign', [SubTaskController::class, 'assign']);
    Route::patch('/subtasks/{id}/unassign', [SubTaskController::class, 'unassign']);
});
