<?php

namespace App\Events;

use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartHeaderEdited
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
        public array $changes,
        public ?string $comment = null,
    ) {
    }
}
