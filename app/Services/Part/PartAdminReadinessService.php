<?php

namespace App\Services\Part;

use App\Models\Part\Part;

class PartAdminReadinessService
{
    public function sync(Part $part): void
    {
        $this->syncOne($part);

        $part->ancestors
            ->unique()
            ->unofficial()
            ->each(fn (Part $p) => $this->syncOne($p));
    }

    protected function syncOne(Part $part): void
    {
        $new = $part->shouldBeReadyForAdmin();

        if ($part->ready_for_admin === $new) {
            return;
        }

        $part->ready_for_admin = $new;
        $part->saveQuietly();
    }
}
