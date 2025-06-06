<?php

namespace Database\Factories\Part;

use App\Enums\License;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartBody;
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
        $type = Arr::random(PartType::cases());
        return [
            'description' => Str::title(fake()->words(5, true)),
            'user_id' => User::factory(),
            'filename' => "{$type->folder()}/" . fake()->numberBetween(1000, 99999) . ".{$type->format()}",
            'type' => $type,
            'license' => License::CC_BY_4,
            'header' => '',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Part $part) {
            $part->generateHeader(false);
        })->afterCreating(function (Part $part) {
            $part->generateHeader(true);
        });
    }

}
