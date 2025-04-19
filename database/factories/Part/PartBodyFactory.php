<?php

namespace Database\Factories\Part;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part\PartBody>
 */
class PartBodyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => '1 0 0 0 0 1 0 0 0 1 0 0 0 1 empty.dat'
        ];
    }
}
