<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public static function holdingInventory(): array
    {
        return [
            self::PENDING->value,
            self::CONFIRMED->value,
            self::CHECKED_IN->value,
        ];
    }
}
