<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = RoomType::class;

    public function definition(): array
    {
        $name = fake()->unique()->bothify('Suite ##??');

        return [
            //
            'property_id' => Property::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'max_adults' => 2,
            'max_children' => 1,
            'max_occupancy' => 3,
            'bed_configuration' => '1 Queen Bed',
            'size_sqm' => 25.00,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
