<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice    = fake()->randomFloat(2, 10, 500);
        $sellingPrice = $costPrice * fake()->randomFloat(2, 1.1, 2.0);

        return [
            'sku'            => strtoupper(fake()->unique()->bothify('??-####')),
            'name'           => fake()->words(3, true),
            'description'    => fake()->optional()->sentence(),
            'category_id'    => \App\Models\Category::factory(),
            'unit_id'        => \App\Models\Unit::factory(),
            'cost_price'     => $costPrice,
            'selling_price'  => round($sellingPrice, 2),
            'stock_quantity' => fake()->randomFloat(3, 0, 200),
            'reorder_level'  => fake()->randomFloat(3, 5, 20),
            'is_active'      => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 2,
            'reorder_level'  => 10,
        ]);
    }
}
