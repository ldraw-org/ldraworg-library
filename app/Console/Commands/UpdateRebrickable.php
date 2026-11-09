<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('lib:update-rebrickable {--a|all-unofficial}')]
#[Description('Call the rebrickable API for unofficial parts')]
class UpdateRebrickable extends Command
{
    public function handle(): void
    {
        Part::unofficial()
            ->canHaveRebrickablePart()
            ->when(
                ! $this->option('all-unofficial'),
                fn (Builder $query) => $query->doesntHave('rebrickable_part')
            )
            ->chunk(200, function ($parts) {
                \App\Jobs\UpdateRebrickable::dispatch($parts);
            });
    }
}
