<?php

use App\Enums\RoomStatus;
use App\Models\Location;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomAvailability;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::connection()->getDatabaseName() !== 'aandaresort_test') {
        throw new RuntimeException('Unsafe testing database.');
    }

    $this->location = Location::factory()->create([
        'slug' => 'test-tagaytay',
        'is_active' => true,
    ]);

    $this->property = Property::factory()->create([
        'location_id' => $this->location->id,
        'slug' => 'test-casa-aurora',
        'is_active' => true,
    ]);

    $this->roomType = RoomType::factory()->create([
        'property_id' => $this->property->id,
        'slug' => 'test-narra',
        'max_adults' => 2,
        'max_children' => 1,
        'max_occupancy' => 3,
        'is_active' => true,
    ]);

    $this->room = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'room_number' => '101',
        'status' => RoomStatus::AVAILABLE,
    ]);

    $plan = RatePlan::create([
        'room_type_id' => $this->roomType->id,
        'name' => 'Standard Rate',
        'code' => 'STANDARD',
        'priority' => 10,
        'min_nights' => 1,
        'is_active' => true,
    ]);

    $plan->rates()->create([
        'amount' => '3500.00',
        'is_active' => true,
    ]);

    $this->availability = app(AvailabilityService::class);
});

function searchRooms(
    AvailabilityService $service,
    int $locationId,
    int $adults = 2,
    int $children = 0,
    string $checkIn = '2026-11-05',
    string $checkOut = '2026-11-08'
): array {
    return $service->search(
        $locationId,
        $checkIn,
        $checkOut,
        $adults,
        $children
    );
}

it('returns an available room with pricing', function () {
    $result = searchRooms(
        $this->availability,
        $this->location->id
    );

    expect($result['total_room_types'])->toBe(1)
        ->and($result['results'][0]['available_units'])->toBe(1)
        ->and($result['results'][0]['pricing']['total'])
        ->toBe('10500.00');
});

it('excludes a room blocked during the requested stay', function () {
    RoomAvailability::create([
        'room_id' => $this->room->id,
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-07',
        'status' => 'blocked',
    ]);

    $result = searchRooms(
        $this->availability,
        $this->location->id
    );

    expect($result['total_room_types'])->toBe(0);
});

it('excludes a room with an overlapping reservation', function () {
    RoomAvailability::create([
        'room_id' => $this->room->id,
        'start_date' => '2026-11-04',
        'end_date' => '2026-11-06',
        'status' => 'reserved',
    ]);

    $result = searchRooms(
        $this->availability,
        $this->location->id
    );

    expect($result['total_room_types'])->toBe(0);
});

it('allows a new check-in on the previous checkout date', function () {
    RoomAvailability::create([
        'room_id' => $this->room->id,
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-05',
        'status' => 'reserved',
    ]);

    $result = searchRooms(
        $this->availability,
        $this->location->id
    );

    expect($result['total_room_types'])->toBe(1);
});

it('excludes rooms under maintenance', function () {
    $this->room->update([
        'status' => RoomStatus::MAINTENANCE,
    ]);

    $result = searchRooms(
        $this->availability,
        $this->location->id
    );

    expect($result['total_room_types'])->toBe(0);
});

it('rejects guests exceeding room capacity', function () {
    $result = searchRooms(
        $this->availability,
        $this->location->id,
        adults: 3
    );

    expect($result['total_room_types'])->toBe(0);
});

it('does not expose rooms from another location', function () {
    $other = Location::factory()->create([
        'slug' => 'other-location',
        'is_active' => true,
    ]);

    $result = searchRooms(
        $this->availability,
        $other->id
    );

    expect($result['total_room_types'])->toBe(0);
});

it('returns the standardized API response', function () {
    $response = $this->postJson(
        '/api/v1/availability/search',
        [
            'location_id' => $this->location->id,
            'check_in' => '2026-11-05',
            'check_out' => '2026-11-08',
            'adults' => 2,
            'children' => 0,
        ]
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_room_types', 1);
});
