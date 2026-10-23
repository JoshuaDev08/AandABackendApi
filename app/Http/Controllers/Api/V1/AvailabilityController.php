<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Availability\SearchAvailabilityRequest;
use App\Services\ApiResponse;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function search(
        SearchAvailabilityRequest $request,
        AvailabilityService $availability
    ): JsonResponse {
        $data = $request->validated();

        $results = $availability->search(
            locationId: (int) $data['location_id'],
            checkIn: $data['check_in'],
            checkOut: $data['check_out'],
            adults: (int) $data['adults'],
            children: (int) ($data['children'] ?? 0),
        );

        return ApiResponse::success(
            $results,
            'Available rooms retrieved successfully.'
        );
    }
}
