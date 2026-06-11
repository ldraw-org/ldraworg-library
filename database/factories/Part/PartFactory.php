<?php

namespace Database\Factories\Part;

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PreviewRotation;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = Arr::random(PartType::datformat());
        $category = $type->inPartsFolder() ? Arr::random(PartCategory::cases()) : null;
        $partNumber = $this->faker->numberBetween(1000, 99999);
        return [
            'description' => Str::title(fake()->words(5, true)),
            'user_id' => User::factory(),
            'filename' => "{$type->folder()}/{$partNumber}.{$type->format()}",
            'type' => $type,
            'category' => $category,
            'license' => License::CC_BY_4,
            'part_status' => PartStatus::Needs2MoreVotes,
            'preview' => PreviewRotation::Default,
            'header' => '',
        ];
    }

    public function unofficial(): static
    {
        return $this->state(['part_release_id' => null]);
    }

    public function official(?PartRelease $release = null): static
    {
        return $this->state(fn () => [
            'part_release_id' => $release?->id ?? PartRelease::factory(),
            'part_status'     => PartStatus::Official,
        ]);
    }

    public function withFix(): static
    {
        return $this->afterCreating(function (Part $official): void {
            $upart = Part::factory()->unofficial()->create([
                'filename' => $official->filename,
            ]);
            $official->unofficial_part()->associate($upart);
            $official->saveQuietly();
        });
    }

    public function withBody(string $body = '0 body text'): static
    {
        return $this->afterCreating(function (Part $part) use ($body): void {
            $part->body()->create(['body' => $body]);
        });
    }

    public function inPartsFolder(): static
    {
        $type = Arr::random(PartType::partsFolderTypes());
        $category = Arr::random(PartCategory::cases());

        return $this->state(fn () => [
            'type'     => $type,
            'filename' => "{$type->folder()}/" . fake()->unique()->numberBetween(1000, 99999) . ".dat",
            'category' => $category,
        ]);
    }

    public function primitive(): static
    {
        $type = Arr::random(PartType::primitiveTypes());

        return $this->state(fn () => [
            'type'     => $type,
            'filename' => "{$type->folder()}/" . fake()->unique()->numberBetween(1000, 99999) . ".dat",
        ]);
    }

}
