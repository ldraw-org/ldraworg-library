<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\Check\CheckMessageCollection;
use App\Services\Check\PartChecker;

class PartCheckService
{
    public function __construct(
        protected PartChecker $checker,
        protected PartAdminReadinessService $adminReadiness,
    )
    {}

    public function checkPart(Part $part, ?string $filename = null): void
    {
        if ($part->isText()) {
            $part->check_messages = $this->checker->run($part);
        } else {
            $part->check_messages = new CheckMessageCollection();
        }
        $part->can_release = $part->isOfficial() || ($part->check_messages->doesntHaveHoldableIssues());
        $part->save();
        $this->adminReadiness->sync($part);
    }

}