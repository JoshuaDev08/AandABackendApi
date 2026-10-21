<?php

use App\Models\Location;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

describe('A&A Resort Location API', function () {

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

    it('returns the locations response successfully', function () {
        $this->getJson('/api/v1/locations')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    });

    it('retrieves the Tagaytay location by slug', function () {
        $this->getJson('/api/v1/locations/tagaytay')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'tagaytay');
    });

    it('returns properties belonging to Tagaytay', function () {
        $response = $this->getJson(
            '/api/v1/locations/tagaytay'
        );

        $response->assertOk();

        $properties = collect(
            $response->json('data.properties')
        );

        expect($properties->pluck('slug')->all())
            ->toContain('casa-aurora', 'casa-beatriz');
    });

    it('does not return properties from other locations', function () {
        $location = Location::where('slug', 'tagaytay')
            ->firstOrFail();

        $response = $this->getJson(
            '/api/v1/locations/tagaytay'
        );

        $response->assertOk();

        $expectedPropertyIds = Property::query()
            ->where('location_id', $location->id)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $actualPropertyIds = collect(
            $response->json('data.properties')
        )->pluck('id')->all();

        expect($actualPropertyIds)
            ->toEqualCanonicalizing($expectedPropertyIds);
    });

    it('returns 404 for an unknown location', function () {
        $this->getJson(
            '/api/v1/locations/non-existing-location-test'
        )
            ->assertNotFound()
            ->assertJsonPath('success', false);
    });
});
