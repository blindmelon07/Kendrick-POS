<?php

namespace Database\Factories;

use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty      = fake()->randomFloat(3, 1, 50);
        $unitCost = fake()->randomFloat(2, 10, 500);

        return [
            'purchase_order_id' => \App\Models\PurchaseOrder::factory(),
            'product_id'        => \App\Models\Product::factory(),
            'product_name'      => fake()->words(3, true),
            'quantity'          => $qty,
            'unit_cost'         => $unitCost,
            'subtotal'          => round($qty * $unitCost, 2),
        ];
    }
}
