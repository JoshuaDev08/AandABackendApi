<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\RoomController;


Route::get('/health', [HealthController::class, 'index']);

Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::get('/health', [HealthController::class, 'index'])
        ->name('health');

    Route::get('/locations', [LocationController::class, 'index'])
        ->name('locations.index');

    Route::get('/locations/{location}', [LocationController::class, 'show'])
        ->name('locations.show');

    Route::get(
        '/locations/{location:slug}/rooms',
        [RoomController::class, 'index']
    )->name('rooms.index');

    Route::get(
        '/locations/{location:slug}/properties/{property}/rooms/{roomType}',
        [RoomController::class, 'show']
    )->name('rooms.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn(Request $request) => $request->user());
    });
});
