<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Location;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly PricingService $pricing
    ) {}

    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {

            $location = Location::query()
                ->whereKey($data['location_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $selectedRooms = collect($data['rooms']);

            if (
                $selectedRooms->isEmpty()
                || $selectedRooms->pluck('room_id')->unique()->count()
                !== $selectedRooms->count()
            ) {
                throw ValidationException::withMessages([
                    'rooms' => ['Select one or more unique rooms.'],
                ]);
            }

            $roomIds = $selectedRooms->pluck('room_id')->all();

            $rooms = Room::query()
                ->with('roomType.property')
                ->whereIn('id', $roomIds)
                ->get()
                ->keyBy('id');

            $totalAdults = 0;
            $totalChildren = 0;
            $totalCentavos = 0;
            $roomSnapshots = [];

            foreach ($selectedRooms as $item) {
                $room = $rooms->get($item['room_id']);

                if (!$room) {
                    throw ValidationException::withMessages([
                        'rooms' => ['The selected room does not exist.'],
                    ]);
                }

                $roomType = $room->roomType;

                if (!$roomType) {
                    throw ValidationException::withMessages([
                        'rooms' => [
                            "Room {$room->id} has no associated room type.",
                        ],
                    ]);
                }

                $property = $roomType->property;

                if (!$property) {
                    throw ValidationException::withMessages([
                        'rooms' => [
                            "Room type {$roomType->id} has no associated property.",
                        ],
                    ]);
                }

                if (
                    !$roomType->is_active ||
                    !$property->is_active ||
                    (int) $property->location_id !== (int) $location->id
                ) {
                    throw ValidationException::withMessages([
                        'rooms' => [
                            'The selected room does not belong to this resort location.',
                        ],
                    ]);
                }

                $adults = (int) $item['adults'];
                $children = (int) ($item['children'] ?? 0);

                $type = $room->roomType;

                if (
                    $adults < 1
                    || $adults > $type->max_adults
                    || $children < 0
                    || $children > $type->max_children
                    || ($adults + $children) > $type->max_occupancy
                ) {
                    throw ValidationException::withMessages([
                        'rooms' => ['Guest capacity exceeded.'],
                    ]);
                }

                $price = $this->pricing->calculate(
                    $type,
                    $data['check_in'],
                    $data['check_out']
                );

                [$pesos, $centavos] = explode('.', $price['total']);

                $amountCentavos = ((int) $pesos * 100)
                    + (int) $centavos;

                $totalCentavos += $amountCentavos;
                $totalAdults += $adults;
                $totalChildren += $children;

                $roomSnapshots[] = [
                    'room_id' => $room->id,
                    'adults' => $adults,
                    'children' => $children,
                    'subtotal' => $price['total'],
                    'pricing_snapshot' => $price,
                ];
            }

            $primary = Guest::create($data['primary_guest']);

            $additionalGuests = [];

            foreach ($data['additional_guests'] ?? [] as $guestData) {
                $additionalGuests[] = Guest::create($guestData);
            }

            // Randomized reference; the database unique constraint
            // remains the final collision safeguard.
            $reference = 'AA-' . strtoupper(Str::random(16));

            $total = sprintf(
                '%d.%02d',
                intdiv($totalCentavos, 100),
                $totalCentavos % 100
            );

            $booking = Booking::create([
                'location_id' => $location->id,
                'primary_guest_id' => $primary->id,
                'reference' => $reference,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adults' => $totalAdults,
                'children' => $totalChildren,
                'status' => BookingStatus::PENDING,
                'payment_status' => PaymentStatus::UNPAID,
                'currency' => 'PHP',
                'subtotal' => $total,
                'total' => $total,
                'special_requests' =>
                $data['special_requests'] ?? null,
            ]);

            foreach ($roomSnapshots as $roomData) {
                $booking->bookingRooms()->create($roomData);
            }

            $booking->bookingGuests()->create([
                'guest_id' => $primary->id,
                'is_primary' => true,
            ]);

            foreach ($additionalGuests as $guest) {
                $booking->bookingGuests()->create([
                    'guest_id' => $guest->id,
                    'is_primary' => false,
                ]);
            }

            return $booking->load([
                'location',
                'primaryGuest',
                'bookingRooms.room.roomType',
                'bookingGuests.guest',
            ]);
        });
    }
}
