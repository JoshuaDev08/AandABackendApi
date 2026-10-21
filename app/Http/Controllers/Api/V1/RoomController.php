<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use App\Models\RoomType;
use App\Services\ApiResponse;
use App\Http\Resources\RoomTypeResource;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Location $location): JsonResponse
    {
        abort_unless($location->is_active, 404);

        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->whereHas('property', function ($query) use ($location) {
                $query
                    ->where('location_id', $location->id)
                    ->where('is_active', true);
            })
            ->with([
                'property.location',
                'amenities' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
                'images' => fn($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            RoomTypeResource::collection($roomTypes)->resolve(),
            'Rooms retrieved successfully.'
        );
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Location $location,
        string $property,
        string $roomType
    ): JsonResponse {
        abort_unless($location->is_active, 404);

        $room = RoomType::query()
            ->where('slug', $roomType)
            ->where('is_active', true)
            ->whereHas('property', function ($query) use (
                $location,
                $property
            ) {
                $query
                    ->where('location_id', $location->id)
                    ->where('slug', $property)
                    ->where('is_active', true);
            })
            ->with([
                'property.location',
                'amenities' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
                'images' => fn($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order'),
            ])
            ->firstOrFail();

        return ApiResponse::success(
            (new RoomTypeResource($room))->resolve(),
            'Room details retrieved successfully.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
