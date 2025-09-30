<?php

namespace App\Jobs;

use App\Services\DB\DatabaseBackup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RestoreDb implements ShouldQueue
{
    use Queueable;

    public function handle(DatabaseBackup $db): void
    {
        $db->restore();
    }
}
