<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'internal_notes',
    ];

    protected function casts(): array {
        return [
            'status' => RoomStatus::class,
        ];
    }

    public function roomtype(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
