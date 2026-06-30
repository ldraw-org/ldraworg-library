<?php

namespace App\Services\User;

use App\Jobs\MassHeaderGenerate;
use App\Models\Part\Part;
use App\Models\User;

class SyncUserParts
{
    public function handle(User $user, array $changes): void
    {
        $relevantFields = ['name', 'real_name', 'license'];
        if (empty(array_intersect($relevantFields, array_keys($changes)))) {
            return;
        }

        if (in_array('license', $changes)) {
            $user->parts()->update(['license' => $user->license]);
        }

        $partIds = $user->parts()->pluck('id')->toArray();

        $shouldSyncHistory = (in_array('name', $changes) && !$user->is_synthetic) ||
            (in_array('real_name', $changes) && $user->is_synthetic);

        if ($shouldSyncHistory) {
            $historyIds = $user->part_history()->pluck('part_id')->toArray();
            $partIds = array_unique(array_merge($partIds, $historyIds));
        }

        if (!empty($partIds)) {
            Part::whereIn('id', $partIds)->update([
                'has_minor_edit' => true,
                'updated_at' => now(),
            ]);
            MassHeaderGenerate::dispatch($partIds);
        }
    }
}
