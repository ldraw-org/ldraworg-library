<?php

namespace App\Services\Support;

use App\Enums\PartCategory;
use Illuminate\Support\Facades\Storage;

class MakeCategoriesTxt
{
    public function handle(): void
    {
        $categories = implode("\n", array_column(PartCategory::cases(), 'value'));
        Storage::put('library/categories.txt', $categories);
    }
}
