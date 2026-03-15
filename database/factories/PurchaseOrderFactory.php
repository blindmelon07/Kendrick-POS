<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number'   => 'PO-' . fake()->unique()->numerify('########'),
            'supplier_id' => \App\Models\Supplier::factory(),
            'status'      => fake()->randomElement(['draft', 'ordered', 'received']),
            'subtotal'    => fake()->randomFloat(2, 100, 10000),
            'tax_amount'  => 0,
            'total'       => fake()->randomFloat(2, 100, 10000),
            'notes'       => null,
            'ordered_at'  => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'expected_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'created_by'  => \App\Models\User::factory(),
        ];
    }
}
