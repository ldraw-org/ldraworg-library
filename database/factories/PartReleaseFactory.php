<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
        return [
            'created_at' => $date,
            'short' => $date->format('y')."{$update}",
            'name' => $date->format('Y')."-{$update}",
            'part_data' => [
                'total_files' => mt_rand(1, 3000),
                'new_files' => mt_rand(1, 3000),
                'new_types' => [
                    ['name' => 'Part', 'count' => mt_rand(1, 3000)],
                    ['name' => 'Primitive', 'count' => mt_rand(1, 3000)],
                ]
            ]
        ];
    }
}
