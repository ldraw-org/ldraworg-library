<?php

namespace App\Console\Commands;

use App\Enums\PartStatus;
use App\Models\TrackerHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        TrackerHistory::each(function (TrackerHistory $trackerHistory) {
            $data = $trackerHistory->history_data;
            if (Arr::has($data, 5)) {
                $data[6] = $data[5];
            }
            if (Arr::has($data, 4)) {
                $data[5] = $data[4];
            }
            $trackerHistory->history_data = $data;
            $trackerHistory->save();
        });
    }
}
