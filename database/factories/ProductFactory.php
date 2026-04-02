<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\CategoryFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        return [
            'name' => $this->faker->word(),
            'price' => $this->faker->numberBetween(10, 100),
            'quantity' => (string) $this->faker->numberBetween(1, 50),
            'category_id' => CategoryFactory::new(),
        ];
    }
}
