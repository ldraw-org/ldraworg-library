<?php

namespace App\Services\Check;

use App\Models\Part\Part;
use App\Services\Check\Contracts\FilenameAwareCheck;
use App\Services\Parser\ParsedPartCollection;

class PartChecker
{

    /** @var array<class-string<BaseCheck>> */
    protected array $submitChecks = [
        \App\Services\Check\PartChecks\HasRequiredHeaderMeta::class,

        \App\Services\Check\PartChecks\LibraryApprovedName::class,
        \App\Services\Check\PartChecks\UnknownPartNumber::class,
        \App\Services\Check\PartChecks\ValidLines::class,
        \App\Services\Check\PartChecks\NoSelfReference::class,

        \App\Services\Check\PartChecks\LibraryApprovedDescription::class,
        \App\Services\Check\PartChecks\LibraryApprovedLicense::class,
        \App\Services\Check\PartChecks\PatternPartDescription::class,
        \App\Services\Check\PartChecks\NameAndPartType::class,
        \App\Services\Check\PartChecks\DescriptionModifier::class,
        \App\Services\Check\PartChecks\NewPartNotPhysicalColor::class,
        \App\Services\Check\PartChecks\AliasInParts::class,
        \App\Services\Check\PartChecks\FlexibleSectionIsPart::class,
        \App\Services\Check\PartChecks\FlexibleHasCorrectSuffix::class,
        \App\Services\Check\PartChecks\BfcIsCcw::class,
        \App\Services\Check\PartChecks\CategoryIsValid::class,
        \App\Services\Check\PartChecks\ObsoletePartIsValid::class,
        \App\Services\Check\PartChecks\PatternHasSetKeyword::class,
        \App\Services\Check\PartChecks\HistoryIsValid::class,
        \App\Services\Check\PartChecks\HistoryUserIsRegistered::class,
        \App\Services\Check\PartChecks\PreviewIsValid::class,
    ];

    /** @var array<class-string<BaseCheck>> */
    protected array $partModelChecks = [
        \App\Services\Check\PartChecks\LibraryApprovedName::class,
        \App\Services\Check\PartChecks\UnknownPartNumber::class,
        \App\Services\Check\PartChecks\ValidLines::class,
        \App\Services\Check\PartChecks\LibraryApprovedDescription::class,
        \App\Services\Check\PartChecks\PatternPartDescription::class,
        \App\Services\Check\PartChecks\NameAndPartType::class,
        \App\Services\Check\PartChecks\DescriptionModifier::class,
        \App\Services\Check\PartChecks\AliasInParts::class,
        \App\Services\Check\PartChecks\FlexibleSectionIsPart::class,
        \App\Services\Check\PartChecks\FlexibleHasCorrectSuffix::class,
        \App\Services\Check\PartChecks\BfcIsCcw::class,
        \App\Services\Check\PartChecks\ObsoletePartIsValid::class,
        \App\Services\Check\PartChecks\PatternHasSetKeyword::class,
        \App\Services\Check\PartChecks\PreviewValuesAreValid::class,
        \App\Services\Check\PartChecks\TrackerHolds::class,
        \App\Services\Check\PartChecks\MinifigCategoryWarning::class,
        \App\Services\Check\PartChecks\LibraryLicenseWarning::class,
        \App\Services\Check\PartChecks\StickerColorWarning::class,
        \App\Services\Check\PartChecks\DescriptionNumberWarning::class,
        \App\Services\Check\PartChecks\DecimalPrecisionWarning::class,
    ];

    public function run(Part|ParsedPartCollection $subject, ?string $filename = null): CheckMessageCollection
    {
        $checks = $subject instanceof ParsedPartCollection
            ? $this->submitChecks
            : $this->partModelChecks;

        return $this->runChecks($checks, $subject, $filename);
    }

    public function runSingle(string $checkClass, Part|ParsedPartCollection $subject, ?string $filename = null): CheckMessageCollection
    {
        return $this->runChecks([$checkClass], $subject, $filename);
    }

    protected function runChecks(array $checkClasses, Part|ParsedPartCollection $subject, ?string $filename = null): CheckMessageCollection
    {
        $results = new CheckMessageCollection();

        foreach ($checkClasses as $checkClass) {
            /** @var BaseCheck $check */
            $check = app($checkClass); // resolves dependencies via Laravel container

            if ($check instanceof FilenameAwareCheck) {
                $check->setFilename($filename);
            }
          
            $results->push(...$check->run($subject));

            if ($check->stopOnError && $results->isNotEmpty()) {
                break;
            }
        }

        return $results;
    }
}