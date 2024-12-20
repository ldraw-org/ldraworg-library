<?php

namespace App\Observers;

use App\Jobs\MassHeaderGenerate;
use App\Models\MybbUser;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function saved(User $user): void
    {
        if ($user->wasChanged(['name', 'realname', 'license'])) {
            if ($user->wasChanged('license')) {
                $user->parts()->update(['license' => $user->license]);
            }
            
            $user->parts()->official()->update(['has_minor_edit' => true]);
            MassHeaderGenerate::dispatch($user->parts);

            if ($user->wasChanged('name')) {
                Part::official()->whereHas('history', fn (Builder $q) => $q->where('user_id', $user->id))->update(['has_minor_edit' => true]);
                MassHeaderGenerate::dispatch(Part::whereHas('history', fn (Builder $q) => $q->where('user_id', $user->id))->get());
            }
        }
        if (app()->environment() == 'production') {
            $mybb = MybbUser::find($user->forum_user_id);
            $mybb->username = $user->realname;
            $mybb->email = $user->email;
            $mybb->loginname = $user->name;
            $mybb_groups = empty($mybb->additionalgroups) ? [] : explode(',', $mybb->additionalgroups);
            foreach (config('ldraw.mybb-groups') as $role => $group) {
                if ($user->hasRole($role) && !in_array($group, $mybb_groups)) {
                    $mybb_groups[] = $group;
                } elseif (!$user->hasRole($role) && in_array($group, $mybb_groups)) {
                    $mybb_groups = array_values(array_filter($mybb_groups, fn ($m) => $m != $group));
                }
            }
            $mybb->additionalgroups = implode(',', $mybb_groups);
            $mybb->save();
        } else {
            Log::debug("User update job run for {$user->name}");
        }
    }

}
