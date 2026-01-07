<?php

namespace App\Services\Part;

use App\Enums\PartStatus;
use App\Models\Part\Part;

class PartStatusSyncService
{
    public function __construct(
        protected PartAdminReadinessService $admin
    )
    {}

    public function sync(Part $part): void
    {
        $oldStatus = $part->part_status;
        $newStatus = $part->determineStatus();

        if ($oldStatus === $newStatus) {
            return;
        }

        $part->part_status = $newStatus;

        if ($oldStatus === PartStatus::Certified && $newStatus !== PartStatus::Certified) {
            $part->marked_for_release = false;
        }

        $part->saveQuietly();

        if ($this->statusAffectsAdminReadiness($oldStatus, $newStatus)) {
            $this->admin->sync($part);
        }
    }

    protected function statusAffectsAdminReadiness(PartStatus $old, PartStatus $new): bool {
        $tracked = [PartStatus::Certified, PartStatus::AwaitingAdminReview];

        return in_array($old, $tracked) xor in_array($new, $tracked);
    }
}
