<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;

Route::get('/health', [HealthController::class, 'index']);

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', [HealthController::class, 'index'])
        ->name('health');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});
