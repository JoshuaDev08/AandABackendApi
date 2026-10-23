<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    public function assertAvailable(
        Room $room,
        string $checkIn,
        string $checkOut
    ): void {
        // 1. Check physical room status.
        $status = $room->status instanceof RoomStatus
            ? $room->status->value
            : $room->status;

        if ($status !== RoomStatus::AVAILABLE->value) {
            throw ValidationException::withMessages([
                'rooms' => [
                    "Room {$room->id} is not operationally available.",
                ],
            ]);
        }

        // 2. Check date-specific restrictions.
        $hasRestriction = $room->availabilities()
            ->where('start_date', '<', $checkOut)
            ->where('end_date', '>', $checkIn)
            ->whereIn('status', [
                'reserved',
                'occupied',
                'blocked',
                'maintenance',
            ])
            ->exists();

        if ($hasRestriction) {
            throw ValidationException::withMessages([
                'rooms' => [
                    "Room {$room->id} is blocked for the selected dates.",
                ],
            ]);
        }

        // 3. Check existing booking assignments.
        $hasBooking = $room->bookingRooms()
            ->whereHas('booking', function ($query) use (
                $checkIn,
                $checkOut
            ) {
                $query
                    ->whereIn(
                        'status',
                        BookingStatus::holdingInventory()
                    )
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->exists();

        if ($hasBooking) {
            throw ValidationException::withMessages([
                'rooms' => [
                    "Room {$room->id} is already reserved for the selected dates.",
                ],
            ]);
        }
    }

    public function validateDates(
        string $checkIn,
        string $checkOut
    ): void {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $checkIn);
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $checkOut);

        if (
            !$start
            || !$end
            || $start->format('Y-m-d') !== $checkIn
            || $end->format('Y-m-d') !== $checkOut
        ) {
            throw ValidationException::withMessages([
                'dates' => ['Invalid booking dates.'],
            ]);
        }

        if ($start->lt(CarbonImmutable::today())) {
            throw ValidationException::withMessages([
                'check_in' => [
                    'Check-in cannot be in the past.',
                ],
            ]);
        }

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'check_out' => [
                    'Check-out must be after check-in.',
                ],
            ]);
        }

        if ((int) $start->diffInDays($end) > 30) {
            throw ValidationException::withMessages([
                'check_out' => [
                    'Maximum stay is 30 nights.',
                ],
            ]);
        }
    }
}
