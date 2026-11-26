<?php

namespace App\Services\LDConfig;

use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Facades\Storage;

class LDConfigFile
{
    protected const string PATH = 'library/official/LDConfig.ldr';

    public function exists(): bool
    {
        return Storage::exists(self::PATH);
    }

    /**
     * The valid lines of the official LDConfig, parsed.
     */
    public function entries(): ParsedPartCollection
    {
        return new ParsedPartCollection(Storage::get(self::PATH))->where('invalid', false);
    }
}
