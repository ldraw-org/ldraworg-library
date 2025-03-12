<?php

namespace App\Observers;

use App\Events\PartDeleted;
use Illuminate\Support\Facades\Auth;
use App\Models\Part\Part;
use App\Models\User;

class PartObserver
{
    public function deleting(Part $part): void
    {
        $part->putDeletedBackup();
        $part->load('parents');
    }

    public function deleted(Part $part)
    {
        $pm = app(\App\LDraw\PartManager::class);
        $part->parents->each(function (Part $p) use ($pm) {
            $pm->loadSubparts($p);
            $pm->checkPart($p);
        });
        PartDeleted::dispatch(Auth::user() ?? User::find(1), $part->filename, $part->description);
    }
}
