<?php

namespace App\Services\Part;

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GenerateHeader
{
    public function updatePartHeader(Part $part): void
    {
        $part->load(['user', 'history', 'keywords', 'release']);
        $part->header = $this->generateHeaderString($part);
        $part->setSearchText();
    }

    public function generateHeaderString(Part $part): string
    {
        $topBlock = [
            "0 {$part->description}", // Added this
            "0 Name: {$part->meta_name}",
            $part->user->toString(),
            $part->license->ldrawString(),
            $this->getLdrawOrgLine($part->isUnofficial(), $part->type, $part->type_qualifier, $part->release)
        ];

        $help = $part->help !== null && count($part->help) > 0 ? '0 !HELP ' . implode("\n0 !HELP ", $part->help) : null;
        $bfc = $this->getBfcLine($part->bfc, $part->isTexmap());


        $catKeyLines = array_filter([
            $part->category->ldrawString(),
            $this->getKeywordLines($part->keywords)
        ]);

        $categoryKeywords = implode("\n", $catKeyLines);

        $cmdline = $part->cmdline !== null && $part->cmdline !== '' ? "0 !CMDLINE {$part->cmdline}" : null;
        $preview = $part->preview?->ldrawString();
        $history = $this->getHistoryLines($part->history);

        $header = implode("\n\n", array_filter([
            implode("\n", $topBlock),
            $help,
            $bfc,
            $categoryKeywords,
            $cmdline,
            $preview,
            $history
        ]));

        return trim($header);
    }

    protected function getLdrawOrgLine(
        bool $isUnofficial,
        PartType $partType,
        ?PartTypeQualifier $partTypeQualifier = null,
        ?PartRelease $partRelease = null,
    ): string
    {
        $ldrawOrgLine = $partType->ldrawString($isUnofficial);
        if ($partTypeQualifier !== null) {
            $ldrawOrgLine  .= " {$partTypeQualifier->value}";
        }
        if ($partRelease !== null) {
            $ldrawOrgLine .= $partRelease->toString();
        }

        return $ldrawOrgLine;
    }

    protected function getBfcLine(?string $bfc, bool $isTexmap): string
    {
        if ($bfc !== null) {
            return "0 BFC CERTIFY {$bfc}";
        }
        if (!$isTexmap) {
            return "0 BFC NOCERTIFY";
        }

        return '';
    }

    protected function getKeywordLines(Collection $keywords): string
    {
        if ($keywords->isEmpty()) {
            return '';
        }

        $lines = [];
        $currentLineContent = '';
        $prefix = '0 !KEYWORDS ';

        foreach ($keywords as $keyword) {
            $word = $keyword->keyword;

            $separator = ($currentLineContent === '') ? '' : ', ';
            $potentialContent = $currentLineContent . $separator . $word;

            if (Str::length($prefix . $potentialContent) > 80) {
                $lines[] = $prefix . $currentLineContent;
                $currentLineContent = $word;
            } else {
                $currentLineContent = $potentialContent;
            }
        }

        if ($currentLineContent !== '') {
            $lines[] = $prefix . $currentLineContent;
        }

        return implode("\n", $lines);
    }

    protected function getHistoryLines(Collection $history): string
    {
        return $history
            ->map(fn (PartHistory $h): string => $h->toString())
            ->implode("\n");

    }
}
