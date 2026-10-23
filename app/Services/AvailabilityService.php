<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use App\Models\RoomType;
use App\Enums\RoomStatus;

class AvailabilityService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private readonly PricingService $pricing)
    {
        //
    }

    public function search(
        int $locationId,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children = 0
    ): array {
        $start = CarbonImmutable::parse($checkIn);
        $end = CarbonImmutable::parse($checkOut);

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'checkout' => ['Checkout must follow check-in.']
            ]);
        }

        $nights = (int) $start->diffInDays($end);

        if ($nights > 30) {
            throw ValidationException::withMessages([
                'checkout' => ['Maximum stay is 30 nights.']
            ]);
        }

        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->where('max_adults', '>=', $adults)
            ->where('max_children', '>=', $children)
            ->where('max_occupancy', '>=', $adults + $children)
            ->whereHas('property', function ($query) use ($locationId) {
                $query
                    ->where('location_id', $locationId)
                    ->where('is_active', true)
                    ->whereHas('location', fn($location) =>
                    $location->where('is_active', true));
            })
            ->with([
                'property.location',
                'rooms' => function ($query) use ($checkIn, $checkOut) {
                    $query
                        ->where('status', RoomStatus::AVAILABLE->value)
                        ->whereDoesntHave('availabilities', function ($q) use (
                            $checkIn,
                            $checkOut
                        ) {
                            $q->where('start_date', '<', $checkOut)
                                ->where('end_date', '>', $checkIn)
                                ->whereIn('status', [
                                    'reserved',
                                    'occupied',
                                    'blocked',
                                    'maintenance',
                                ]);
                        });
                },
            ])
            ->get();

        $results = [];

        foreach ($roomTypes as $roomType) {
            $availableCount = $roomType->rooms->count();

            if ($availableCount === 0) {
                continue;
            }

            try {
                //code...
                $price = $this->pricing->calculate(
                    $roomType,
                    $checkIn,
                    $checkOut
                );
            } catch (ValidationException $exception) {
                //throw $th;
                continue;
            }

            $results[] = [
                'room_type_id' => $roomType->id,
                'name' => $roomType->name,
                'slug' => $roomType->slug,
                'location' => [
                    'id' => $roomType->property->location->id,
                    'name' => $roomType->property->location->name,
                    'slug' => $roomType->property->location->slug,
                ],
                'property' => [
                    'id' => $roomType->property->id,
                    'name' => $roomType->property->name,
                    'slug' => $roomType->property->slug,
                ],
                'capacity' => [
                    'max_adults' => $roomType->max_adults,
                    'max_children' => $roomType->max_children,
                    'max_occupancy' => $roomType->max_occupancy,
                ],
                'available_units' => $availableCount,
                'pricing' => $price,
            ];
        }

        return [
            'search' => [
                'location_id' => $locationId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'adults' => $adults,
                'children' => $children,
            ],
            'results' => $results,
            'total_room_types' => count($results),
        ];
    }
}
