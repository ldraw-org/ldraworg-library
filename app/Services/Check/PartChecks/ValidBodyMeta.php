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
        if ($part instanceof Part) {
            $header_length = substr_count($part->header, "\n") + 2;
            $body = explode("\n", $part->body->body);
        } else {
            $header_length = $part->header_length;
            $body = explode("\n", $part->body);
        }
        foreach ($body as $index => $line) {
            $line = Str::squish($line);
            if (empty($line)) {
                continue;
            }
            $lineStart = Str::words($line, 2, '');
            if (Str::startsWith($lineStart, '0') && !Str::endsWith($lineStart, $this->settings->allowed_body_metas)) {
                $message(PartError::InvalidLineType0, ['value' => $index + $header_length]);
            }
        }
    }
}
