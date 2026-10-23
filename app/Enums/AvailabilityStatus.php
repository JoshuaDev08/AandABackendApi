<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case OCCUPIED = 'occupied';
    case BLOCKED = 'blocked';
    case MAINTENANCE = 'maintenance';
}
