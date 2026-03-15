<?php

namespace Database\Factories;

use App\Models\DeliveryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryItem>
 */
class DeliveryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expected = fake()->randomFloat(3, 1, 50);

        return [
            'delivery_order_id'   => \App\Models\DeliveryOrder::factory(),
            'product_id'          => \App\Models\Product::factory(),
            'product_name'        => fake()->words(3, true),
            'expected_quantity'   => $expected,
            'received_quantity'   => 0,
            'unit_cost'           => fake()->randomFloat(2, 10, 500),
            'notes'               => null,
        ];
    }
}
