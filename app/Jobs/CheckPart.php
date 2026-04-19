<?php

namespace App\Jobs;

use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Services\Part\Validator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPart implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected array $partIds
    ) {
    }

    public function handle(Validator $validator): void
    {
        $parts = Part::with(['user', 'history', 'body'])->whereIn('id', $this->partIds)->get();
        $parts->each(fn (Part $part) => $validator->checkPart($part));
    }
}
