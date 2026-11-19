<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\RebrickablePart\Writer as RebrickablePartWriter;

/**
 * Re-syncs official and unofficial parts after a Rebrickable import: relinks them, updates their external
 * site keywords and regenerates their headers. An official part whose keywords change becomes a minor edit.
 */
class RelinkRebrickableParts
{
    protected const int CHUNK = 500;

    public function __construct(
        protected RebrickablePartWriter $rebrickablePartWriter,
        protected RebrickableSync $rebrickableSync,
    ) {
    }

    /**
     * @param array<int, int> $changedRebrickablePartIds Rebrickable parts whose external ids changed
     * @return int the number of parts re-synced
     */
    public function handle(array $changedRebrickablePartIds): int
    {
        $changed = array_flip($changedRebrickablePartIds);
        $count = 0;

        Part::canHaveRebrickablePart()
            ->with('rebrickable_part')
            ->lazyById(self::CHUNK)
            ->filter(fn (Part $part) => $part->canSetRebrickablePart() && $this->needsSync($part, $changed))
            ->each(function (Part $part) use (&$count) {
                $this->rebrickableSync->syncRebrickablePart($part, updateOfficial: true);
                $count++;
            });

        return $count;
    }

    /**
     * @param array<int, int> $changed
     */
    protected function needsSync(Part $part, array $changed): bool
    {
        $current = $part->rebrickable_part;

        // Local sticker sheets are retried in case Rebrickable now lists the real sheet
        if (!is_null($current) && (isset($changed[$current->id]) || $current->is_local)) {
            return true;
        }

        // Unlinked stickers are left alone, as matching one creates a local placeholder sheet
        if ($part->category->isSticker()) {
            return false;
        }

        $found = $this->rebrickablePartWriter->findForPart($part);

        return !is_null($found) && $found->id !== $current?->id;
    }
}
