<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class DescriptionNumberWarning extends BaseCheck
{
    protected $wordsBefore = [
        'Type',
        'Phase',
        'Functions',
    ];
    protected $wordsAfter  = [
        'Ball', 
        'Balls', 
        'Stripe', 
        'Stripes',
        'White',
    ];

    public function check(): iterable
    {
        $beforePattern = '';
        foreach ($this->wordsBefore as $word) {
            $beforePattern .= '(?<!' . preg_quote($word, '~') . '\s)';
        }

        $afterPattern = '';
        if ($this->wordsAfter) {
            $escaped = array_map(
                fn ($w) => preg_quote($w, '~'),
                $this->wordsAfter
            );
            $afterPattern = '(?!\s+(?:' . implode('|', $escaped) . ')\b)';
        }

        $regex = "~(?<!\s)\s{$beforePattern}\d(?:\.\d+)?\b(?![\d\"\/-]){$afterPattern}~iu";

        if (preg_match_all($regex, $this->part->description())) {
            yield $this->error(
                CheckType::Warning,
                PartError::WarningDescriptionNumberSpaces
            );
        }
    }
}