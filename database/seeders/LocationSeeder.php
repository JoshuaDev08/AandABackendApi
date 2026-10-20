<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Property;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $tagaytay = Location::updateOrCreate(
            ['slug' => 'tagaytay'],
            [
                'name' => 'A&A Garden Suites - Tagaytay',
                'short_description' =>
                'A peaceful garden escape near Tagaytay.',
                'description' =>
                'Discover garden accommodations and relaxing stays.',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
                'country' => 'Philippines',
                'timezone' => 'Asia/Manila',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ]
        );

        $properties = [
            [
                'name' => 'Casa Aurora',
                'slug' => 'casa-aurora',
                'sort_order' => 1,
            ],
            [
                'name' => 'Casa Beatriz',
                'slug' => 'casa-beatriz',
                'sort_order' => 2,
            ],
        ];

        foreach ($properties as $property) {
            Property::updateOrCreate(
                [
                    'location_id' => $tagaytay->id,
                    'slug' => $property['slug'],
                ],
                [
                    'name' => $property['name'],
                    'is_active' => true,
                    'sort_order' => $property['sort_order'],
                ]
            );
        }
    }
}
