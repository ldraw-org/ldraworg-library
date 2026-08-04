<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
class CheckPart implements ShouldQueue
{
    use Queueable;

    protected Collection $parts;

    public function __construct(Part|Collection $parts)
    {
        $this->parts = $parts instanceof Part
            ? new Collection([$parts])
            : $parts;
    }

    public function handle(Validator $validator): void
    {
        $this->parts->load(['user', 'history', 'body']);
        $this->parts->each(fn (Part $part) => $validator->checkPart($part));
    }
}
