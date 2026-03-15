<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 5000);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $total    = $subtotal - $discount;
        $tendered = ceil($total / 10) * 10 + (fake()->boolean(30) ? fake()->numberBetween(0, 100) : 0);

        return [
            'reference_no'    => 'TXN-' . fake()->unique()->numerify('########'),
            'cashier_id'      => \App\Models\User::factory(),
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'tax_amount'      => 0,
            'total'           => $total,
            'payment_method'  => fake()->randomElement(['cash', 'gcash', 'credit_card']),
            'amount_tendered' => $tendered,
            'change_amount'   => $tendered - $total,
            'status'          => 'completed',
            'voided_by'       => null,
            'voided_at'       => null,
            'void_reason'     => null,
        ];
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'voided',
            'voided_by'   => \App\Models\User::factory(),
            'voided_at'   => now(),
            'void_reason' => fake()->sentence(),
        ]);
    }
}
