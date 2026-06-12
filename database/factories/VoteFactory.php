<?php

namespace Database\Factories;

use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class VoteFactory extends Factory
{
    public function definition(): array
    {
        $type = Arr::random(VoteType::storedVoteTypes());
        return [
            'user_id' => User::factory(),
            'part_id' => Part::factory(),
            'vote_type' => $type,
        ];
    }

}
