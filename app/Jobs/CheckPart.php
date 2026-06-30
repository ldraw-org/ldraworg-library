<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\Validator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use function PHPUnit\Framework\isInt;

class CheckPart implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int|array $partIds
    ) {
        if (isInt($this->partIds)) {
            $this->partIds = [$this->partIds];
        }
    }

    public function handle(Validator $validator): void
    {
        $parts = Part::with(['user', 'history', 'body'])->whereIn('id', $this->partIds)->get();
        $parts->each(fn (Part $part) => $validator->checkPart($part));
    }
}
