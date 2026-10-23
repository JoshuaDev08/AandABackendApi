<?php

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::connection()->getDatabaseName() !== 'aandaresort_test') {
        throw new RuntimeException('Unsafe testing database.');
    }

    $location = Location::factory()->create([
        'slug' => 'test-tagaytay',
        'is_active' => true,
    ]);

    $property = Property::factory()->create([
        'location_id' => $location->id,
        'is_active' => true,
    ]);

    $type = RoomType::factory()->create([
        'property_id' => $property->id,
        'max_adults' => 2,
        'max_children' => 1,
        'max_occupancy' => 3,
        'is_active' => true,
    ]);

    $this->room = Room::factory()->create([
        'room_type_id' => $type->id,
        'room_number' => '101',
        'status' => RoomStatus::AVAILABLE,
    ]);

    $plan = RatePlan::create([
        'room_type_id' => $type->id,
        'name' => 'Standard',
        'code' => 'STANDARD',
        'priority' => 10,
        'min_nights' => 1,
        'is_active' => true,
    ]);

    $plan->rates()->create([
        'amount' => '3500.00',
        'is_active' => true,
    ]);

    $this->payload = [
        'location_id' => $location->id,
        'check_in' => '2026-11-05',
        'check_out' => '2026-11-08',
        'primary_guest' => [
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => 'test@example.com',
        ],
        'rooms' => [
            [
                'room_id' => $this->room->id,
                'adults' => 2,
                'children' => 0,
            ],
        ],
    ];
});

it('prevents overlapping reservations', function () {
    $service = app(BookingService::class);

    $first = $service->create($this->payload);

    expect($first->status)->toBe(BookingStatus::PENDING);

    $second = $this->payload;

    $second['check_in'] = '2026-11-06';
    $second['check_out'] = '2026-11-09';

    expect(fn() => $service->create($second))
        ->toThrow(ValidationException::class);

    expect(Booking::count())->toBe(1);
});

it('allows check-in on a previous checkout date', function () {
    $service = app(BookingService::class);

    $first = $service->create($this->payload);

    $second = $this->payload;

    $second['check_in'] = '2026-11-08';
    $second['check_out'] = '2026-11-10';

    $nextBooking = $service->create($second);

    expect($first->id)->not->toBe($nextBooking->id)
        ->and(Booking::count())->toBe(2);
});

it('rejects scheduled room blocks', function () {
    RoomAvailability::create([
        'room_id' => $this->room->id,
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-07',
        'status' => 'blocked',
    ]);

    expect(fn() => app(BookingService::class)
        ->create($this->payload))
        ->toThrow(ValidationException::class);

    expect(Booking::count())->toBe(0);
});

it('rejects rooms in maintenance', function () {
    $this->room->update([
        'status' => RoomStatus::MAINTENANCE,
    ]);

    expect(fn() => app(BookingService::class)
        ->create($this->payload))
        ->toThrow(ValidationException::class);
});

it('rejects excessive guest capacity', function () {
    $payload = $this->payload;

    $payload['rooms'][0]['adults'] = 4;

    expect(fn() => app(BookingService::class)
        ->create($payload))
        ->toThrow(ValidationException::class);
});

it('rejects checkout before check-in', function () {
    $payload = $this->payload;

    $payload['check_in'] = '2026-11-08';
    $payload['check_out'] = '2026-11-05';

    expect(fn() => app(BookingService::class)
        ->create($payload))
        ->toThrow(ValidationException::class);
});

it('allows a new booking after cancellation', function () {
    $service = app(BookingService::class);

    $first = $service->create($this->payload);

    $first->update([
        'status' => BookingStatus::CANCELLED,
    ]);

    $second = $service->create($this->payload);

    expect($second->id)->not->toBe($first->id)
        ->and(Booking::count())->toBe(2);
});
