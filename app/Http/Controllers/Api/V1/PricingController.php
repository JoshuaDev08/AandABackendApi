<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\PriceQuoteRequest;
use App\Models\Location;
use App\Models\RoomType;
use App\Services\ApiResponse;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    public function quote(
        PriceQuoteRequest $request,
        Location $location,
        string $property,
        string $roomType,
        PricingService $pricing
    ): JsonResponse {
        abort_unless($location->is_active, 404);

        $room = RoomType::query()
            ->where('slug', $roomType)
            ->where('is_active', true)
            ->whereHas('property', fn($query) => $query
                ->where('location_id', $location->id)
                ->where('slug', $property)
                ->where('is_active', true))
            ->firstOrFail();

        $validated = $request->validated();

        $quote = $pricing->calculate(
            $room,
            $validated['check_in'],
            $validated['check_out']
        );

        return ApiResponse::success(
            $quote,
            'Pricing calculated successfully.'
        );
    }
}
