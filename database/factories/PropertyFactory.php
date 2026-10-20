<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Property> */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $name = 'Casa ' . fake()->unique()->firstName();

        return [
            'location_id' => Location::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
