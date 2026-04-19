<?php

namespace App\Services\Part;

use App\Models\Part\Part;
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
            $part->check_messages = $this->partChecker->run($part);
        } else {
            $part->check_messages = new CheckMessageCollection();
        }
        $part->can_release = $part->isOfficial() || ($part->check_messages->doesntHaveHoldableIssues());
        $part->updateReadyForAdmin();
        $part->save();
    }
}
