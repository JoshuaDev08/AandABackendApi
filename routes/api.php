<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\LocationMediaController;
use App\Http\Controllers\Api\V1\PricingController;


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

    Route::get(
        '/locations/{location:slug}/properties/{property}/rooms/{roomType}/pricing',
        [PricingController::class, 'quote']
    )->name('rooms.pricing');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn(Request $request) => $request->user());
    });

    Route::middleware([
        'auth:sanctum',
        'can:manage-media',
    ])->prefix('admin')->group(function () {

        Route::post(
            '/locations/{location:slug}/featured-image',
            [LocationMediaController::class, 'store']
        )->name('admin.locations.image.store');

        Route::delete(
            '/locations/{location:slug}/featured-image',
            [LocationMediaController::class, 'destroy']
        )->name('admin.locations.image.destroy');
    });
});
