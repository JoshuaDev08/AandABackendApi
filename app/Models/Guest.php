<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    //
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    public function booking(): HasMany
    {
        return $this->hasMany(
            Booking::class,
            'primary_guest_id'
        );
    }

    public function bookingGuests(): HasMany
    {
        return $this->hasMany(BookingGuest::class);
    }
}
