<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            'capacity' => [
                'max_adults' => $this->max_adults,
                'max_children' => $this->max_children,
                'max_occupancy' => $this->max_occupancy,
            ],

            'bed_configuration' => $this->bed_configuration,
            'size_sqm' => $this->size_sqm,

            'location' => $this->whenLoaded(
                'property',
                fn() => [
                    'id' => $this->property->location->id,
                    'name' => $this->property->location->name,
                    'slug' => $this->property->location->slug,
                ]
            ),

            'property' => $this->whenLoaded(
                'property',
                fn() => [
                    'id' => $this->property->id,
                    'name' => $this->property->name,
                    'slug' => $this->property->slug,
                ]
            ),

            'amenities' => AmenityResource::collection(
                $this->whenLoaded('amenities')
            ),

            'images' => RoomImageResource::collection(
                $this->whenLoaded('images')
            ),
        ];
    }
}
