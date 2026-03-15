<?php

namespace Database\Factories;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty    = fake()->randomFloat(3, 1, 50);
        $before = fake()->randomFloat(3, 0, 100);
        $type   = fake()->randomElement(['in', 'out', 'adjustment']);
        $after  = $type === 'out' ? max(0, $before - $qty) : $before + $qty;

        return [
            'product_id'      => \App\Models\Product::factory(),
            'type'            => $type,
            'quantity'        => $qty,
            'before_quantity' => $before,
            'after_quantity'  => $after,
            'reason'          => fake()->randomElement(['Purchase', 'Sale', 'Adjustment', 'Damage', 'Return']),
            'reference'       => fake()->optional()->numerify('REF-######'),
            'notes'           => null,
            'user_id'         => \App\Models\User::factory(),
        ];
    }
}
