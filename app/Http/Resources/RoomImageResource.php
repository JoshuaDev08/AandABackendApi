<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class RoomImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            // Keep your other existing fields.

            'featured_image' => $this->featured_image
                ? $disk->url($this->featured_image)
                : null,

            'properties' => PropertyResource::collection(
                $this->whenLoaded('properties')
            ),
        ];
    }
}
