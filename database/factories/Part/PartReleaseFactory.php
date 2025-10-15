<?php

namespace Database\Factories\Part;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\PartType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part\PartRelease>
 */
class PartReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min_date = new \DateTime('1996-01-01');
        $date = new \DateTime();
        $date->setTimestamp(mt_rand($min_date->getTimestamp(), time()));
        $update = mt_rand(1, 99);
        if ($update <= 9) {
            $update = "0{$update}";
        }
        $new_of_type = [];
        foreach(PartType::cases() as $type) {
            $new_of_type[$type->value] = mt_rand(0, 3000);
        }
        return [
            'created_at' => $date,
            'short' => $date->format('y')."{$update}",
            'name' => $date->format('Y')."-{$update}",
            'total' => mt_rand(1, 3000),
            'new' => mt_rand(0, 3000),
            'new_of_type' => $new_of_type,
        ];
    }
}
