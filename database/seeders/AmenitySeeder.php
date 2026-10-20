<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'Wi-Fi', 'slug' => 'wifi', 'icon' => 'wifi', 'category' => 'general'],
            ['name' => 'Parking', 'slug' => 'parking', 'icon' => 'car', 'category' => 'general'],
            ['name' => 'Air Conditioning', 'slug' => 'air-conditioning', 'icon' => 'wind', 'category' => 'room'],
            ['name' => 'Television', 'slug' => 'television', 'icon' => 'tv', 'category' => 'room'],
            ['name' => 'Private Bathroom', 'slug' => 'private-bathroom', 'icon' => 'bath', 'category' => 'bathroom'],
            ['name' => 'Swimming Pool', 'slug' => 'swimming-pool', 'icon' => 'waves', 'category' => 'outdoor'],
            ['name' => 'Garden', 'slug' => 'garden', 'icon' => 'trees', 'category' => 'outdoor'],
            ['name' => 'Breakfast', 'slug' => 'breakfast', 'icon' => 'coffee', 'category' => 'food'],
        ];

        foreach ($amenities as $index => $amenity) {
            Amenity::updateOrCreate(
                ['slug' => $amenity['slug']],
                [
                    ...$amenity,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
