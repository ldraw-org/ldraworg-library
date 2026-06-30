<?php

namespace Database\Factories\Part;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part\PartKeyword>
 */
class PartKeywordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numWords = $this->faker->numberBetween(1,3);
        $words = $this->faker->words($numWords, true);
        if ($numWords > 1 && $this->faker->boolean()) {
            $words = "\"{$words}\"";
        } else {
            $words = ucfirst($words);
        }

        return [
            'keyword' => $words,
        ];
    }
}
