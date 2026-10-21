<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tagaytay = Location::query()
            ->where('slug', 'tagaytay')
            ->firstOrFail();

        $aurora = $tagaytay->properties()
            ->where('slug', 'casa-aurora')
            ->firstOrFail();

        // Temporary development-only record.
        // Capacity and unit inventory must be verified
        // before publishing or accepting bookings.
        RoomType::firstOrCreate(
            [
                'property_id' => $aurora->id,
                'slug' => 'narra-demo',
            ],
            [
                'name' => 'Narra (Demo)',
                'description' => 'Development placeholder.',
                'max_adults' => 1,
                'max_children' => 0,
                'max_occupancy' => 1,
                'is_active' => false,
                'sort_order' => 1,
            ]
        );
    }
}
