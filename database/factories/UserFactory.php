<?php

namespace Database\Factories;

use App\Enums\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->userName(),
            'realname' => fake()->firstName() . " " . fake()->lastName(),
            'email' => fake()->safeEmail(),
            'email_verified_at' => now(),
            'license' => License::CC_BY_4,
            'password' => bcrypt(Str::random(40)), // password
            'remember_token' => Str::random(10),
        ];
    }
}
