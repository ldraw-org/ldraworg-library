<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\Check\CheckMessage;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\PartChecker;

class Validator
{
    public function __construct(
        protected PartChecker $partChecker,
    )
    {}

    public function checkPart(Part $part, ?string $filename = null): void
    {
        if ($part->isText()) {
            $part->check_messages()->delete();
            $messages = $this->partChecker->run($part)->map(fn (CheckMessage $check) => $check->toArray())->toArray();
            $part->check_messages()->createMany($messages);
        }
        $part->can_release = $part->isOfficial() || ($part->check_messages->doesntHaveHoldableIssues());
        $part->updateReadyForAdmin();
        $part->save();
    }
}
