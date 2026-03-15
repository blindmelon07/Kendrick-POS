<?php

namespace Database\Factories;

use App\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrder>
 */
class DeliveryOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_number'   => 'DLV-' . fake()->unique()->numerify('########'),
            'supplier_id'       => \App\Models\Supplier::factory(),
            'purchase_order_id' => null,
            'status'            => fake()->randomElement(['pending', 'in_transit', 'received']),
            'notes'             => null,
            'shipped_at'        => fake()->optional()->dateTimeBetween('-10 days', 'now'),
            'expected_at'       => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'received_at'       => null,
            'created_by'        => \App\Models\User::factory(),
        ];
    }
}
