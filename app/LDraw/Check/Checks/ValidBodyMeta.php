<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Check\Contracts\SettingsAwareCheck;
use App\LDraw\Parse\ParsedPart;
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

    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $header_length = count(explode("\n", $part->header)) + 2;
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
                $fail(PartError::InvalidLineType0, ['value' => $index + $header_length]);
            }
        }
    }
}
