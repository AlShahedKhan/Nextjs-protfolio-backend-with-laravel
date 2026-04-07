<?php

use App\Http\Controllers\Api\Admin\ProjectController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Public\ProjectController as PublicProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/projects', [PublicProjectController::class, 'index']);
    Route::get('/projects/{slug}', [PublicProjectController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')
        ->prefix('admin')
        ->group(function (): void {
            Route::apiResource('projects', ProjectController::class);
        });
});
