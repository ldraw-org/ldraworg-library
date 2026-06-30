<?php

namespace Database\Factories;

use App\Models\Part\Part;
use App\Services\Check\Enums\CheckType;
use App\Services\Check\Enums\PartAutomatedHold;
use App\Services\Check\Enums\PartError;
use App\Services\Check\Enums\PartWarning;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class CheckMessageFactory extends Factory
{
    public function definition(): array
    {
        $checkType = Arr::random(CheckType::cases());
        $checkItems = match ($checkType) {
            CheckType::Error => PartError::cases(),
            CheckType::Warning => PartWarning::cases(),
            CheckType::TrackerHold => PartAutomatedHold::cases(),
        };
        return [
            'part_id' => Part::factory(),
            'error' => Arr::random($checkItems),
            'check_type' => $checkType,
        ];
    }
}
