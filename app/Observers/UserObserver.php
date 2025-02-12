<?php

namespace App\Observers;

use App\Jobs\MassHeaderGenerate;
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
            $user->forum_user->username = $user->realname;
            $user->forum_user->email = $user->email;
            $user->forum_user->loginname = $user->name;
            foreach (config('ldraw.mybb-groups') as $role => $group) {
                if ($user->hasRole($role)) {
                    $user->forum_user->addGroup($group);
                }
            }
            $user->forum_user->save();
        } else {
            Log::debug("User update job run for {$user->name}");
        }
    }

}
