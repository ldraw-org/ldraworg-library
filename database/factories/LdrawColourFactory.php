<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LdrawColour>
 */
class LdrawColourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'code' => $this->faker->unique()->numberBetween(1, 20000),
            'value' => $this->faker->unique()->hexColor(),
            'edge' => $this->faker->hexColor(),
        ];
    }
}
