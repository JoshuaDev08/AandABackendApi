<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\UploadImageRequest;
use App\Models\Location;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ApiResponse;
use App\Http\Resources\LocationResource;

class LocationMediaController extends Controller
{
    //
    public function __construct(private readonly MediaService $media) {}

    public function store(UploadImageRequest $request, Location $location): JsonResponse
    {
        $directory = "locations/{$location->slug}/featured";

        $newPath = $this->media->upload($request->file('image'), $directory);

        $oldPath = $location->featured_image;

        try {
            //code...
            DB::transaction(function () use ($location, $newPath) {
                $location->update([
                    'featured_image' => $newPath,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->media->delete($newPath);
            throw $exception;
        }

        // Delete the previous file only after the DB update succeeds.
        if ($oldPath && ! $this->media->delete($oldPath)) {
            Log::warning('Old location image could not be deleted', [
                'location_id' => $location->id,
                'path' => $oldPath,
            ]);
        }

        return ApiResponse::success(
            (new LocationResource($location->fresh()))->resolve(),
            'Location featured image updated successfully.'
        );
    }

    public function destroy(Location $location): JsonResponse
    {
        $oldPath = $location->featured_image;

        if (!$oldPath) {
            return ApiResponse::success(
                null,
                'Location has no featured image.'
            );
        }

        // Remove the database reference first.
        $location->update([
            'featured_image' => null,
        ]);

        // Delete the actual file.
        $deleted = $this->media->delete($oldPath);

        if (!$deleted) {
            \Illuminate\Support\Facades\Log::warning(
                'Failed to delete location featured image',
                [
                    'location_id' => $location->id,
                    'path' => $oldPath,
                ]
            );
        }

        return ApiResponse::success(
            null,
            'Location featured image removed successfully.'
        );
    }
}
