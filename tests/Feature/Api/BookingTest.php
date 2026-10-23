
<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Location;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {

    // Ensure tests never modify the development database.
    if (DB::connection()->getDatabaseName() !== 'aandaresort_test') {
        throw new RuntimeException('Unsafe testing database.');
    }

    // 1. Create resort location.
    $this->location = Location::factory()->create([
        'slug' => 'test-tagaytay',
        'is_active' => true,
    ]);

    // 2. Create property belonging to location.
    $this->property = Property::factory()->create([
        'location_id' => $this->location->id,
        'is_active' => true,
    ]);

    // 3. Create room type belonging to property.
    $this->roomType = RoomType::factory()->create([
        'property_id' => $this->property->id,
        'max_adults' => 2,
        'max_children' => 1,
        'max_occupancy' => 3,
        'is_active' => true,
    ]);

    // 4. Create TWO physical rooms under the same room type.
    $this->room1 = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'room_number' => '101',
    ]);

    $this->room2 = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'room_number' => '102',
    ]);

    // 5. Create a pricing plan.
    $plan = RatePlan::create([
        'room_type_id' => $this->roomType->id,
        'name' => 'Standard',
        'code' => 'STANDARD',
        'priority' => 10,
        'min_nights' => 1,
        'is_active' => true,
    ]);

    $plan->rates()->create([
        'amount' => '3500.00',
        'days_of_week' => null,
        'is_active' => true,
    ]);

    // 6. Define the booking request payload.
    $this->payload = [
        'location_id' => $this->location->id,
        'check_in' => '2026-11-02',
        'check_out' => '2026-11-04',

        'primary_guest' => [
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => 'test@example.com',
        ],

        'additional_guests' => [
            [
                'first_name' => 'Second',
                'last_name' => 'Guest',
            ],
        ],

        'rooms' => [
            [
                'room_id' => $this->room1->id,
                'adults' => 2,
                'children' => 0,
            ],
            [
                'room_id' => $this->room2->id,
                'adults' => 1,
                'children' => 0,
            ],
        ],
    ];
});

/*
|--------------------------------------------------------------------------
| Database relationship tests
|--------------------------------------------------------------------------
*/

it('correctly associates rooms with their room type and property', function () {

    $room1 = Room::with('roomType.property')
        ->findOrFail($this->room1->id);

    $room2 = Room::with('roomType.property')
        ->findOrFail($this->room2->id);

    expect($room1->room_type_id)
        ->toBe($this->roomType->id)

        ->and($room2->room_type_id)
        ->toBe($this->roomType->id)

        ->and($room1->roomType)
        ->not->toBeNull()

        ->and($room2->roomType)
        ->not->toBeNull()

        ->and($room1->roomType->property)
        ->not->toBeNull()

        ->and($room1->roomType->property->location_id)
        ->toBe($this->location->id);
});

/*
|--------------------------------------------------------------------------
| Booking creation tests
|--------------------------------------------------------------------------
*/

it('creates a booking with multiple rooms and guests', function () {

    $booking = app(BookingService::class)
        ->create($this->payload);

    expect($booking)
        ->toBeInstanceOf(Booking::class)

        ->and($booking->bookingRooms)
        ->toHaveCount(2)

        ->and($booking->bookingGuests)
        ->toHaveCount(2)

        ->and($booking->location_id)
        ->toBe($this->location->id)

        ->and($booking->status)
        ->toBe(BookingStatus::PENDING)

        ->and($booking->payment_status)
        ->toBe(PaymentStatus::UNPAID)

        ->and($booking->reference)
        ->toStartWith('AA-');

    $this->assertDatabaseHas('booking_rooms', [
        'booking_id' => $booking->id,
        'room_id' => $this->room1->id,
    ]);

    $this->assertDatabaseHas('booking_rooms', [
        'booking_id' => $booking->id,
        'room_id' => $this->room2->id,
    ]);
});

it('calculates the total for two rooms and two nights', function () {

    $booking = app(BookingService::class)
        ->create($this->payload);

    // 2 rooms x 2 nights x PHP 3,500 = PHP 14,000.
    expect($booking->total)
        ->toBe('14000.00')

        ->and($booking->subtotal)
        ->toBe('14000.00');

    foreach ($booking->bookingRooms as $bookingRoom) {
        expect($bookingRoom->subtotal)->toBe('7000.00');
    }
});

it('generates unique booking references', function () {

    $first = app(BookingService::class)
        ->create($this->payload);

    // Separate booking with the same room selection.
    // Day 10 will prohibit overlapping reservations.
    $second = app(BookingService::class)
        ->create($this->payload);

    expect($first->reference)
        ->not->toBe($second->reference);

    expect(Booking::count())->toBe(2);
});

it('rolls back if a selected room belongs to another location', function () {

    $otherLocation = Location::factory()->create([
        'slug' => 'another-location',
        'is_active' => true,
    ]);

    $otherProperty = Property::factory()->create([
        'location_id' => $otherLocation->id,
        'is_active' => true,
    ]);

    $otherType = RoomType::factory()->create([
        'property_id' => $otherProperty->id,
        'is_active' => true,
    ]);

    $otherRoom = Room::factory()->create([  
        'room_type_id' => $otherType->id,
    ]);

    $invalid = $this->payload;

    $invalid['rooms'][1]['room_id'] = $otherRoom->id;

    expect(fn() => app(BookingService::class)->create($invalid))
        ->toThrow(ValidationException::class);

    expect(Booking::count())
        ->toBe(0)

        ->and(Guest::count())
        ->toBe(0);
});

it('rejects duplicate physical rooms in one booking', function () {

    $invalid = $this->payload;

    $invalid['rooms'][1]['room_id'] =
        $invalid['rooms'][0]['room_id'];

    expect(fn() => app(BookingService::class)->create($invalid))
        ->toThrow(ValidationException::class);

    expect(Booking::count())->toBe(0);
});
