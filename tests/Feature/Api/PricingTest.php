<?php

use App\Models\Location;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::connection()->getDatabaseName() !== 'aandaresort_test') {
        throw new RuntimeException('Unsafe testing database.');
    }

    $location = Location::query()->create([
        'name' => 'Test Tagaytay',
        'slug' => 'test-tagaytay',
        'is_active' => true,
    ]);

    $property = Property::query()->create([
        'location_id' => $location->id,
        'name' => 'Test Casa Aurora',
        'slug' => 'test-casa-aurora',
        'is_active' => true,
    ]);

    $this->room = RoomType::query()->create([
        'property_id' => $property->id,
        'name' => 'Test Narra',
        'slug' => 'test-narra',
        'max_adults' => 2,
        'max_children' => 0,
        'max_occupancy' => 2,
        'is_active' => true,
    ]);

    $standard = RatePlan::create([
        'room_type_id' => $this->room->id,
        'name' => 'Standard',
        'code' => 'STANDARD',
        'priority' => 10,
        'min_nights' => 1,
        'is_active' => true,
    ]);

    $weekend = RatePlan::create([
        'room_type_id' => $this->room->id,
        'name' => 'Weekend',
        'code' => 'WEEKEND',
        'priority' => 20,
        'min_nights' => 1,
        'is_active' => true,
    ]);

    $standard->rates()->create([
        'amount' => '3500.00',
        'days_of_week' => null,
        'is_active' => true,
    ]);

    $weekend->rates()->create([
        'amount' => '4200.00',
        'days_of_week' => [5, 6],
        'is_active' => true,
    ]);

    $this->pricing = app(PricingService::class);
});

it('calculates a weekday rate', function () {
    $quote = $this->pricing->calculate(
        $this->room,
        '2026-11-02',
        '2026-11-03'
    );

    expect($quote['nights'])->toBe(1)
        ->and($quote['total'])->toBe('3500.00')
        ->and($quote['breakdown'][0]['rate_code'])
        ->toBe('STANDARD');
});

it('calculates a weekend rate', function () {
    $quote = $this->pricing->calculate(
        $this->room,
        '2026-11-06',
        '2026-11-07'
    );

    expect($quote['total'])->toBe('4200.00')
        ->and($quote['breakdown'][0]['rate_code'])
        ->toBe('WEEKEND');
});

it('calculates a three-night mixed rate correctly', function () {
    $quote = $this->pricing->calculate(
        $this->room,
        '2026-11-05',
        '2026-11-08'
    );

    expect($quote['nights'])->toBe(3)
        ->and($quote['total'])->toBe('11900.00')
        ->and(array_column($quote['breakdown'], 'amount'))
        ->toBe(['3500.00', '4200.00', '4200.00']);
});

it('does not charge for the checkout date', function () {
    $quote = $this->pricing->calculate(
        $this->room,
        '2026-11-06',
        '2026-11-08'
    );

    expect($quote['nights'])->toBe(2)
        ->and($quote['total'])->toBe('8400.00');
});

it('rejects checkout before check-in', function () {
    expect(fn() => $this->pricing->calculate(
        $this->room,
        '2026-11-08',
        '2026-11-05'
    ))->toThrow(ValidationException::class);
});

it('applies a higher priority holiday rate', function () {
    $holiday = RatePlan::create([
        'room_type_id' => $this->room->id,
        'name' => 'Holiday',
        'code' => 'HOLIDAY',
        'priority' => 100,
        'min_nights' => 1,
        'is_active' => true,
    ]);

    $holiday->rates()->create([
        'amount' => '6000.00',
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-06',
        'days_of_week' => null,
        'is_active' => true,
    ]);

    $quote = $this->pricing->calculate(
        $this->room,
        '2026-11-05',
        '2026-11-08'
    );

    expect($quote['total'])->toBe('13700.00')
        ->and($quote['breakdown'][1]['rate_code'])
        ->toBe('HOLIDAY');
});

it('respects minimum night requirements', function () {
    RatePlan::where('code', 'WEEKEND')
        ->where('room_type_id', $this->room->id)
        ->update(['min_nights' => 2]);

    $quote = $this->pricing->calculate(
        $this->room,
        '2026-11-06',
        '2026-11-07'
    );

    expect($quote['total'])->toBe('3500.00');
});
