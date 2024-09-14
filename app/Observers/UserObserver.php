<?php

namespace App\Observers;

use App\Models\MybbUser;
use App\Models\Part;
use App\Models\PartLicense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function saved(User $user): void
    {
        if ($user->wasChanged(['name', 'realname', 'part_license_id'])) {
            $user->parts->each(function (Part $p) use ($user) {
                $md = $p->minor_edit_data;
                if ($user->wasChanged('part_license_id')) {
                    $ol = PartLicense::find($user->getOriginal('part_license_id'));
                    $p->license()->associate($user->license);
                    $md['license'] = "{$ol->name} to {$user->license->name}";
                }
                if ($user->wasChanged(['name', 'realname'])) {
                    $md['user'] = "User {$user->name} data changed";
                }
                if (!$p->isUnofficial()) {
                    $p->minor_edit_data = $md;
                }
                $p->generateHeader();
            });
            if ($user->wasChanged(['name', 'realname'])) {
                Part::whereHas('history', fn (Builder $q) => $q->where('user_id', $user->id))
                    ->each(function (Part $p) use ($user) {
                        if (!$p->isUnofficial()) {
                            $md = $p->minor_edit_data;
                            $md['user'] = "User {$user->name} data changed";
                            $p->minor_edit_data = $md;
                        }
                        $p->generateHeader();
                    });
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
