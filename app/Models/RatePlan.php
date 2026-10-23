<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class RatePlan extends Model
{
    //
    protected $fillable = [
        'room_type_id',
        'name',
        'code',
        'description',
        'priority',
        'min_nights',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'min_nights' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(RoomRate::class, 'rate_plan_id');
    }
}
