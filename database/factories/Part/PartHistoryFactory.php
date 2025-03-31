<?php

namespace Database\Factories\Part;

use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PartHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'part_id' => Part::factory(),
            'created_at' => fake()->dateTimeBetween('1996-01-01', 'now'),
            'comment' => fake()->sentence(),
        ];
    }
}
