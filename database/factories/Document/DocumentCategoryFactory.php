<?php

namespace Database\Factories\Document;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class DocumentCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $data = [
            'title' => fake()->words(5, true),
            'order' => 1,
        ];
        $data['slug'] = Str::slug($data['title']);
        return $data;
    }
}
