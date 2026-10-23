<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'slug' => $this->location->slug,
            ],

            'check_in' => $this->check_in->format('Y-m-d'),
            'check_out' => $this->check_out->format('Y-m-d'),

            'guests_count' => [
                'adults' => $this->adults,
                'children' => $this->children,
            ],

            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,

            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'total' => $this->total,

            'primary_guest' => [
                'first_name' => $this->primaryGuest->first_name,
                'last_name' => $this->primaryGuest->last_name,
                'email' => $this->primaryGuest->email,
            ],

            'rooms' => $this->bookingRooms->map(
                fn($item) => [
                    'room_type' => $item->room->roomType->name,
                    'adults' => $item->adults,
                    'children' => $item->children,
                    'subtotal' => $item->subtotal,
                    'pricing' => $item->pricing_snapshot,
                ]
            )->values()->all(),

            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
