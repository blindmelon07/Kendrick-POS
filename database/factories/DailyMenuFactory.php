<?php

namespace Database\Factories;

use App\Models\DailyMenu;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyMenu>
 */
class DailyMenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id'    => Product::factory(),
            'featured_date' => today()->toDateString(),
            'sort_order'    => fake()->numberBetween(0, 10),
        ];
    }

    public function forDate(string $date): static
    {
        return $this->state(['featured_date' => $date]);
    }

    public function today(): static
    {
        return $this->state(['featured_date' => today()->toDateString()]);
    }
}
