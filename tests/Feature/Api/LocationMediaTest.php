<?php

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::connection()->getDatabaseName() !== 'aandaresort_test') {
        throw new RuntimeException('Unsafe testing database.');
    }

    Storage::fake('public');

    // Give the test user permission without requiring
    // the production admin-role system to exist yet.
    Gate::define('manage-media', fn(User $user) => true);

    $this->actingAs(User::factory()->create(), 'sanctum');
});

it('uploads a location image successfully', function () {
    $location = Location::factory()->create([
        'slug' => 'tagaytay',
    ]);

    $response = $this->postJson(
        "/api/v1/admin/locations/{$location->slug}/featured-image",
        [
            'image' => UploadedFile::fake()->image('tagaytay.jpg'),
        ]
    );

    $response->assertOk()
        ->assertJsonPath('success', true);

    $location->refresh();

    expect($location->featured_image)->not->toBeNull();

    Storage::disk('public')->assertExists(
        $location->featured_image
    );
});

it('rejects an invalid image', function () {
    $location = Location::factory()->create([
        'slug' => 'tagaytay',
    ]);

    $this->postJson(
        "/api/v1/admin/locations/{$location->slug}/featured-image",
        [
            'image' => UploadedFile::fake()->create(
                'document.pdf',
                100,
                'application/pdf'
            ),
        ]
    )->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('image');
});

it('replaces the previous featured image', function () {
    $location = Location::factory()->create([
        'slug' => 'tagaytay',
    ]);

    $url = "/api/v1/admin/locations/{$location->slug}/featured-image";

    // First upload
    $first = $this->postJson($url, [
        'image' => UploadedFile::fake()->image('first.jpg'),
    ]);

    $first->assertOk();

    $oldPath = $location->fresh()->featured_image;

    expect(Storage::disk('public')->exists($oldPath))->toBeTrue();

    // Second upload
    $second = $this->postJson($url, [
        'image' => UploadedFile::fake()->image('second.jpg'),
    ]);

    $second->assertOk();

    $newPath = $location->fresh()->featured_image;

    dump([
        'old_path' => $oldPath,
        'new_path' => $newPath,
        'same_path' => $oldPath === $newPath,
        'old_file_exists' => Storage::disk('public')->exists($oldPath),
        'new_file_exists' => Storage::disk('public')->exists($newPath),
    ]);

    expect($newPath)->not->toBe($oldPath);

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('deletes a location featured image', function () {
    $location = Location::factory()->create([
        'slug' => 'tagaytay',
    ]);

    $url = "/api/v1/admin/locations/{$location->slug}/featured-image";

    $this->postJson($url, [
        'image' => UploadedFile::fake()->image('tagaytay.jpg'),
    ])->assertOk();

    $path = $location->fresh()->featured_image;

    $this->deleteJson($url)->assertOk()
        ->assertJsonPath('success', true);

    expect($location->fresh()->featured_image)->toBeNull();

    Storage::disk('public')->assertMissing($path);
});
