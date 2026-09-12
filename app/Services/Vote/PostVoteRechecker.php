<?php

namespace App\Services\Vote;

use App\Models\Part\Part;
use App\Services\Part\Validator;

class PostVoteRechecker
{
    public function __construct(
        private readonly Validator $validator
    ) {}

    public function recheck(Part $part): void
    {
        // Call the relations as methods (not properties) so `unofficial()`
        // applies as a query scope before the results are fetched.
        $part->parentsAndSelf()
            ->unofficial()
            ->get()
            ->merge($part->descendants()->unofficial()->get())
            ->unique()
            ->each(fn (Part $p) => $this->validator->checkPart($p));
    }
}
