<?php

namespace App\Services\Check;

use App\Enums\CheckType;
use App\Services\Check\Traits\InteractsWithCheckMessages;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, CheckMessage>
 */
class CheckMessageCollection extends Collection
{
    use InteractsWithCheckMessages;

    public static function fromArray(array $messages): self
    {
        return static::make(
            collect($messages)
                ->filter()
                ->map(fn ($message) =>
                $message instanceof CheckMessage
                    ? $message
                    : CheckMessage::fromArray($message)
                )
                ->values()
        );
    }
}
