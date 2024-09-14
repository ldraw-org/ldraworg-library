<?php

namespace App\Events;

use App\Models\Part;
use App\Models\PartRelease;
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
        public Part $part,
        public User $user,
        public PartRelease $release,
    ) {
    }
}
