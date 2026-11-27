<?php

namespace App\Services\User;

use App\Jobs\MassHeaderGenerate;
use App\Models\Part\Part;
use App\Models\User;

class SyncUserParts
{
    /**
     * Brings the user's parts in line with a change to the user.
     *
     * @param  array<string, mixed>  $changes  the user's changed attributes, keyed by name, as getChanges() returns them
     */
    public function handle(User $user, array $changes): void
    {
        $changed = array_keys($changes);
        if (empty(array_intersect(['name', 'realname', 'license'], $changed))) {
            return;
        }

        if (in_array('license', $changed, true)) {
            $user->parts()->update(['license' => $user->license]);
        }

        $partIds = $user->parts()->pluck('id')->toArray();

        // History lines name synthetic users by real name and everyone else by username
        $shouldSyncHistory = in_array($user->is_synthetic ? 'realname' : 'name', $changed, true);

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
