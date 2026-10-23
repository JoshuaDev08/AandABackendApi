<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    //

    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'email',
        'phone',
        'check_in_time',
        'check_out_time',
        'timezone',
        'featured_image',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
