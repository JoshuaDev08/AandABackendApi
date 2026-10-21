<?php

use App\Models\Location;
use App\Models\RoomType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $database = DB::connection()->getDatabaseName();

    if (
        app()->environment() !== 'testing' ||
        $database !== 'aandaresort'
    ) {
        throw new RuntimeException(
            'Unexpected testing database: ' . $database
        );
    }
});

describe('Day 05 - Room API', function () {

    it('returns room types for Tagaytay', function () {
        $this->getJson('/api/v1/locations/tagaytay/rooms')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    });

    it('uses our standardized ApiResponse format', function () {
        $this->getJson('/api/v1/locations/tagaytay/rooms')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Rooms retrieved successfully.'
            )
            ->assertJsonIsArray('data');
    });

    it('returns only room types belonging to Tagaytay', function () {
        $location = Location::query()
            ->where('slug', 'tagaytay')
            ->firstOrFail();

        $expectedIds = RoomType::query()
            ->where('is_active', true)
            ->whereHas('property', function ($query) use ($location) {
                $query
                    ->where('location_id', $location->id)
                    ->where('is_active', true);
            })
            ->pluck('id')
            ->all();

        $response = $this->getJson(
            '/api/v1/locations/tagaytay/rooms'
        )->assertOk();

        $actualIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        expect($actualIds)
            ->toEqualCanonicalizing($expectedIds);
    });

    it('retrieves an existing published room by its scoped URL', function () {
        $room = RoomType::query()
            ->where('is_active', true)
            ->whereHas('property', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('location', function ($location) {
                        $location
                            ->where('slug', 'tagaytay')
                            ->where('is_active', true);
                    });
            })
            ->with('property.location')
            ->first();

        if (!$room) {
            $this->markTestSkipped(
                'No active Tagaytay room type is available.'
            );
        }

        $url = sprintf(
            '/api/v1/locations/%s/properties/%s/rooms/%s',
            $room->property->location->slug,
            $room->property->slug,
            $room->slug
        );

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.slug', $room->slug)
            ->assertJsonPath(
                'data.property.slug',
                $room->property->slug
            )
            ->assertJsonPath(
                'data.location.slug',
                'tagaytay'
            )
            ->assertJsonStructure([
                'data' => [
                    'capacity' => [
                        'max_adults',
                        'max_children',
                        'max_occupancy',
                    ],
                    'amenities',
                    'images',
                ],
            ]);
    });

    it('does not return a room from an incorrect property', function () {
        $room = RoomType::query()
            ->where('is_active', true)
            ->whereHas('property', function ($query) {
                $query->where('is_active', true)
                    ->whereHas('location', fn($q) => $q
                        ->where('slug', 'tagaytay')
                        ->where('is_active', true));
            })
            ->with('property.location')
            ->first();

        if (!$room) {
            $this->markTestSkipped(
                'No active Tagaytay room type is available.'
            );
        }

        $url = sprintf(
            '/api/v1/locations/tagaytay/properties/%s/rooms/%s',
            'not-a-real-property',
            $room->slug
        );

        $this->getJson($url)
            ->assertNotFound()
            ->assertJsonPath('success', false);
    });

    it('returns 404 for an unknown room', function () {
        $this->getJson(
            '/api/v1/locations/tagaytay/properties/casa-aurora/rooms/unknown-room-test'
        )
            ->assertNotFound()
            ->assertJsonPath('success', false);
    });

    it('returns 404 for an unknown location', function () {
        $this->getJson(
            '/api/v1/locations/unknown-location-test/rooms'
        )
            ->assertNotFound()
            ->assertJsonPath('success', false);
    });

    it('returns an image gallery for published room types', function () {
        $room = RoomType::query()
            ->where('is_active', true)
            ->whereHas('property', fn($query) => $query
                ->where('is_active', true)
                ->whereHas('location', fn($q) => $q
                    ->where('slug', 'tagaytay')
                    ->where('is_active', true)))
            ->whereHas('images')
            ->with('property.location')
            ->first();

        if (!$room) {
            $this->markTestSkipped(
                'No published Tagaytay room type has images.'
            );
        }

        $url = sprintf(
            '/api/v1/locations/%s/properties/%s/rooms/%s',
            $room->property->location->slug,
            $room->property->slug,
            $room->slug
        );

        $response = $this->getJson($url)
            ->assertOk();

        $images = $response->json('data.images');

        expect($images)
            ->toBeArray()
            ->not->toBeEmpty();

        foreach ($images as $image) {
            expect($image)
                ->toHaveKeys([
                    'id',
                    'url',
                    'alt_text',
                    'is_primary',
                    'sort_order',
                ]);

            expect($image['url'])->toBeString();
        }
    });

    it('can access the Tagaytay featured image in storage', function () {
        $location = Location::query()
            ->where('slug', 'tagaytay')
            ->firstOrFail();

        expect($location->featured_image)
            ->not->toBeNull();

        expect(
            Storage::disk('public')->exists(
                $location->featured_image
            )
        )->toBeTrue();

        $response = $this->getJson(
            '/api/v1/locations/tagaytay'
        )->assertOk();

        expect($response->json('data.featured_image'))
            ->toBeString()
            ->toContain('/storage/');
    });
});
