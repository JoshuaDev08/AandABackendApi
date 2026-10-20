<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\LocationResource;


class LocationController extends Controller
{
    //
    public function index(): JsonResponse
    {
        $locations = Location::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            LocationResource::collection($locations)->resolve(),
            'Locations retrieved successfully.'
        );
    }

    /**
     * Display a specific resort location.
     */
    public function show(Location $location): JsonResponse
    {
        abort_unless($location->is_active, 404);

        $location->load([
            'properties' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name'),
        ]);

        return ApiResponse::success(
            (new LocationResource($location))->resolve(),
            'Location retrieved successfully.'
        );
    }
}
