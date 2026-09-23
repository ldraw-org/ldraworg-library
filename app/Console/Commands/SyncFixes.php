<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:sync-fixes')]
#[Description('Retie unofficial part fixes to the official parts')]
class SyncFixes extends Command
{
    public function handle(): void
    {
        $parts = Part::unofficial()
            ->whereDoesntHave('official_part')
            ->get();
        \App\Jobs\SyncFixes::dispatch($parts);
    }
}
