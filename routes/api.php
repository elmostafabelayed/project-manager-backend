<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\NotificationController;

Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth:sanctum');
Route::get('/users/{id}/profile', [ProfileController::class, 'publicShow']);
Route::post('/profile', [ProfileController::class, 'update'])->middleware('auth:sanctum');

Route::post('/reviews', [ReviewController::class, 'store'])
    ->middleware('auth:sanctum');
Route::post('/messages', [MessageController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/conversations', [ConversationController::class, 'index'])
    ->middleware('auth:sanctum');

Route::get('/conversations/{id}/messages', [MessageController::class, 'index'])
    ->middleware('auth:sanctum');
Route::post('/proposals', [ProposalController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/my-proposals', [ProposalController::class, 'myProposals'])
    ->middleware('auth:sanctum');

Route::get('/skills', [SkillController::class, 'index']);
Route::post('/user/skills', [SkillController::class, 'sync'])
    ->middleware('auth:sanctum');

Route::get('/projects/{id}/proposals', [ProposalController::class, 'index']);

Route::put('/proposals/{id}/accept', [ProposalController::class, 'accept'])
    ->middleware('auth:sanctum');
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/my-projects', [ProjectController::class, 'myProjects'])
    ->middleware('auth:sanctum');

Route::post('/projects', [ProjectController::class, 'store'])
    ->middleware('auth:sanctum');

Route::post('/projects/from-proposal', [ProjectController::class, 'createFromProposal'])
    ->middleware('auth:sanctum');

Route::put('/projects/{id}', [ProjectController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
    ->middleware('auth:sanctum');
Route::get('/notifications', [NotificationController::class, 'index'])
    ->middleware('auth:sanctum');
Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
    ->middleware('auth:sanctum');
Route::put('/notifications/messages/read', [NotificationController::class, 'markAllMessageNotificationsRead'])
    ->middleware('auth:sanctum');
Route::put('/notifications/conversation/{conversationId}/read', [NotificationController::class, 'markConversationNotificationsRead'])
    ->middleware('auth:sanctum');
Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
    ->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// Admin Routes
Route::prefix('admin')->middleware('auth:sanctum')->group(function() {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/projects', [AdminController::class, 'projects']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::delete('/projects/{id}', [AdminController::class, 'deleteProject']);
});
