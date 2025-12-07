<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Public authentication routes
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    // Project API routes
    Route::apiResource('projects', ProjectController::class);

    // Task API routes
    Route::apiResource('tasks', TaskController::class);

    // Specialized task updates
    Route::patch('tasks/{id}/status', [TaskController::class, 'updateStatus']);
    Route::patch('tasks/{id}/priority', [TaskController::class, 'updatePriority']);
    Route::patch('tasks/{id}/assignees', [TaskController::class, 'updateAssignees']);

    // Comment routes
    Route::get('comments', [CommentController::class, 'index']);
    Route::post('comments', [CommentController::class, 'store']);
    Route::delete('comments/{id}', [CommentController::class, 'destroy']);

    // Notification routes
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::patch('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
});
