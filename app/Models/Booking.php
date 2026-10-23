<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Booking extends Model
{
    //
    protected $fillable = [
        'location_id',
        'primary_guest_id',
        'reference',
        'check_in',
        'check_out',
        'adults',
        'children',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'total',
        'special_requests',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'adults' => 'integer',
            'children' => 'integer',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function primaryGuest(): BelongsTo
    {
        return $this->belongsTo(
            Guest::class,
            'primary_guest_id'
        );
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function bookingGuests(): HasMany
    {
        return $this->hasMany(BookingGuest::class);
    }
}
