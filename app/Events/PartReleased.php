<?php

namespace App\Events;

use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartReleased
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $partId,
        public int $actorId,
        public int $releaseId,
        public string $releaseName,
    ) {
    }
}
