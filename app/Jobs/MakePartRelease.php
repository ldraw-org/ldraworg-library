<?php

namespace App\Jobs;

use App\LDraw\PartsUpdateProcessor;
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
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 1800;
    public $timeout = 1800;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Collection $parts, 
        public User $user
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $updater = new PartsUpdateProcessor($this->parts, $this->user);
        $updater->createRelease();
    }
}
