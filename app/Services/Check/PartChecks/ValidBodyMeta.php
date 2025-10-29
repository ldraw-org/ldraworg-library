<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Check\Contracts\SettingsAwareCheck;
use App\Services\Parser\ParsedPartCollection;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use Closure;
use Illuminate\Support\Str;

class ValidBodyMeta implements Check, SettingsAwareCheck
{
    protected LibrarySettings $settings;

    public function setSettings(LibrarySettings $settings): void
    {
        $this->settings = $settings;
    }

    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $part->bodyLines()
            ->whereNotIn('meta', [
                'comment',
                'texmap',
                'texmap_geometry',
                'bfc',
            ])
            ->where('linetype', 0)
            ->each(fn (array $line) => $message(PartError::InvalidLineType0, $line['line_number']));
    }
}
