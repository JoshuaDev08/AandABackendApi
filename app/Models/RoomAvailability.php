<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\AvailabilityStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAvailability extends Model
{
    //
    protected $fillable = [
        'room_id',
        'start_date',
        'end_date',
        'status',
        'reason'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'status' => AvailabilityStatus::class,
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
