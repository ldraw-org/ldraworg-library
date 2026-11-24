<?php

namespace App\Enums;

use App\Enums\Traits\CanBeOption;

/**
 * The milestones of a parts release, in the order they run.
 */
enum ReleaseStage: string
{
    use CanBeOption;

    case Queued = 'queued';
    case SyncingRebrickable = 'syncing_rebrickable';
    case BackingUp = 'backing_up';
    case ReleasingParts = 'releasing_parts';
    case MakingZips = 'making_zips';
    case Cleanup = 'cleanup';
    case Complete = 'complete';

    public function isBefore(ReleaseStage $other): bool
    {
        return array_search($this, self::cases(), true) < array_search($other, self::cases(), true);
    }

    protected function customLabel(): ?string
    {
        return match ($this) {
            ReleaseStage::SyncingRebrickable => 'Syncing Rebrickable data',
            ReleaseStage::BackingUp => 'Backing up the database',
            ReleaseStage::ReleasingParts => 'Releasing parts',
            ReleaseStage::MakingZips => 'Making and publishing the zips',
            ReleaseStage::Cleanup => 'Post-release cleanup',
            default => null,
        };
    }
}
