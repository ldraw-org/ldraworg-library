<?php

namespace App\Jobs;

use App\Services\LDraw\PartsUpdateProcessor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

class MakePartRelease implements ShouldQueue, ShouldBeUnique
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $uniqueFor = 3600;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Collection $parts,
        public User $user,
        public bool $includeLdconfig = false,
        public array $extraFiles = []
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $updater = new PartsUpdateProcessor($this->parts, $this->user, $this->includeLdconfig, $this->extraFiles);
        $updater->createRelease();
    }
}
