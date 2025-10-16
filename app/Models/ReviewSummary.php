<?php

namespace App\Models;

use App\Models\Part\Part;
use App\Models\Traits\HasOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ReviewSummary extends Model
{
    use HasOrder;

    protected $guarded = [];

    public function parts(): Collection
    {
        $parts = [];

        foreach (explode("\n", $this->list) as $item) {
            if (Str::of($item)->trim()->doesntStartWith('/')) {
                $parts[] = $item;
            }
        }

        return Part::doesntHave('unofficial_part')->whereIn('filename', $parts)->get();
    }
}
