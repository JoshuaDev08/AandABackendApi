<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = fake()->unique()->city() . ' Resort';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'country' => 'Philippines',
            'timezone' => 'Asia/Manila',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
