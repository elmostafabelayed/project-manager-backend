<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;

Route::get('/projects', [ProjectController::class, 'index']);

Route::post('/projects', [ProjectController::class, 'store'])
    ->middleware('auth:sanctum');

Route::put('/projects/{id}', [ProjectController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
    ->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);