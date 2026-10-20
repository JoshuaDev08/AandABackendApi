<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    
     public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,

            'address' => [
                'street' => $this->address,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],

            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
            ],

            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,

            'featured_image' => $this->featured_image,
            'is_featured' => $this->is_featured,

            'properties' => PropertyResource::collection(
                $this->whenLoaded('properties')
            ),
        ];
    }
}
