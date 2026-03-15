<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = [
            ['name' => 'Piece', 'abbreviation' => 'pc'],
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Liter', 'abbreviation' => 'L'],
            ['name' => 'Box', 'abbreviation' => 'box'],
            ['name' => 'Pack', 'abbreviation' => 'pk'],
        ];
        $unit = fake()->unique()->randomElement($units);

        return [
            'name'         => $unit['name'],
            'abbreviation' => $unit['abbreviation'],
        ];
    }
}
