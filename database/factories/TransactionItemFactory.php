<?php

namespace Database\Factories;

use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty      = fake()->randomFloat(3, 1, 10);
        $price    = fake()->randomFloat(2, 10, 500);
        $discount = 0;
        $subtotal = round($qty * $price - $discount, 2);

        return [
            'transaction_id'  => \App\Models\Transaction::factory(),
            'product_id'      => \App\Models\Product::factory(),
            'product_name'    => fake()->words(3, true),
            'sku'             => strtoupper(fake()->bothify('??-####')),
            'quantity'        => $qty,
            'unit_price'      => $price,
            'discount_amount' => $discount,
            'subtotal'        => $subtotal,
        ];
    }
}
