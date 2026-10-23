<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoom extends Model
{
    //
    protected $fillable = [
        'booking_id',
        'room_id',
        'adults',
        'children',
        'subtotal',
        'pricing_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'adults' => 'integer',
            'children' => 'integer',
            'subtotal' => 'decimal:2',
            'pricing_snapshot' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
