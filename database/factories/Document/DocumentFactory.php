<?php

namespace Database\Factories\Document;

use App\Enums\DocumentType;
use App\Models\Document\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class DocumentFactory extends Factory
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
            'document_category_id' => DocumentCategory::factory(),
            'type' => DocumentType::Markdown->value,
            'order' => 1,
            'maintainer' => fake()->name(),
            'revision_history' => fake()->sentences(asText: true),
            'content' => fake()->paragraph(),
            'published' => false,
            'restricted' => false,
            'draft' => false,
        ];
        $data['slug'] = Str::slug($data['title']);
        return $data;
    }
}
