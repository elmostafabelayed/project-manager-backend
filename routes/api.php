<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReviewController;

Route::post('/reviews', [ReviewController::class, 'store'])
    ->middleware('auth:sanctum');
Route::post('/messages', [MessageController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/conversations/{id}/messages', [MessageController::class, 'index'])
    ->middleware('auth:sanctum');
Route::post('/proposals', [ProposalController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/projects/{id}/proposals', [ProposalController::class, 'index']);

Route::put('/proposals/{id}/accept', [ProposalController::class, 'accept'])
    ->middleware('auth:sanctum');
Route::get('/projects', [ProjectController::class, 'index']);

Route::post('/projects', [ProjectController::class, 'store'])
    ->middleware('auth:sanctum');

Route::put('/projects/{id}', [ProjectController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
    ->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
