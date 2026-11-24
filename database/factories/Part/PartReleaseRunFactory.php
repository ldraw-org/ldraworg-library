<?php

namespace Database\Factories\Part;

use App\Enums\ReleaseStage;
use App\Models\Part\PartReleaseRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part\PartReleaseRun>
 */
class PartReleaseRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => PartReleaseRun::CURRENT_ID,
            'user_id' => User::factory(),
            'stage' => ReleaseStage::Queued,
        ];
    }

    public function complete(): static
    {
        return $this->state(['stage' => ReleaseStage::Complete]);
    }

    public function failed(): static
    {
        return $this->state(['stage' => ReleaseStage::MakingZips, 'error' => 'The zips could not be written']);
    }
}
