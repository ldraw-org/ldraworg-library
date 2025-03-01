<?php

namespace App\LDraw\Check;

use App\Enums\License;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Check\Contracts\FilenameAwareCheck;
use App\LDraw\Check\Contracts\SettingsAwareCheck;
use App\Models\User;
use App\Models\Part\Part;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\PartCategory;
use App\Settings\LibrarySettings;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use MathPHP\LinearAlgebra\MatrixFactory;

class PartChecker
{
    protected MessageBag $errors;

    public function __construct(
        protected LibrarySettings $settings
    ) {
        $this->errors = new MessageBag();
    }

    public function runChecks(Part|ParsedPart $part, array $checks = [], ?string $filename = null): bool {
        $this->errors = new MessageBag();
        foreach ($checks as $check) {
            if (!$check instanceof Check) {
                continue;
            }
            if ($check instanceof FilenameAwareCheck) {
                $check->setFilename($filename);
            }
            if ($check instanceof SettingsAwareCheck) {
                $check->setSettings($this->settings);
            }
            $check->check($part, Closure::fromCallable([$this, 'addError']));
            if ($this->hasErrors() && property_exists($check, 'stopOnError')) {
                break;
            }
        }
        return $this->hasErrors();
    }

    public function addError(string $error): void
    {
        $this->errors->add('errors', $error);
    }

    public function hasErrors(): bool
    {
        return $this->errors->isNotEmpty();
    }

    public function getErrors(): array
    {
        return $this->errors->get('errors');
    }

    public function checkSubmittedPart(ParsedPart $part, ?string $filename = null): array
    {
        $ferrors = $this->checkFile($part, $filename);
        $herrors = $this->checkHeader($part);
        return array_merge($herrors, $ferrors);
    }

    public function checkCanRelease(Part $part): array
    {
        $part->loadMissing('descendants', 'ancestors');
        $errors = [];
        if (!$part->isTexmap()) {
            $ferrors = $this->checkFile($part);
            $herrors = $this->checkHeader($part);
            $errors = array_merge($herrors, $ferrors);
        }

        if ($part->isUnofficial()) {
            $hascertparents = !is_null($part->official_part) ||
                $part->type->inPartsFolder() || $part->type == PartType::Helper ||
                $this->hasCertifiedParentInParts($part);
            if (!$hascertparents) {
                $errors[] = 'No certified parents in the parts directory';
            }
            if (!$this->hasAllSubpartsCertified($part)) {
                $errors[] = 'Has uncertified subfiles';
            }
            if (count($part->missing_parts) > 0) {
                $errors[] = 'Has missing part references';
            }
            if ($part->manual_hold_flag) {
                $errors[] = 'Manual hold back by admin';
            }
            if ($part->license !== License::CC_BY_4) {
                $errors[] = "Part License {$part->license->value} not authorized for library";
            }
        }
        $can_release = count($errors) == 0;
        return compact('can_release', 'errors');
    }

    public function hasCertifiedParentInParts(Part $part): bool
    {
        return $part->ancestors->whereIn('type', PartType::partsFolderTypes())->where('part_status', PartStatus::Certified)->count() > 0;
    }

    public function hasAllSubpartsCertified(Part $part): bool
    {
        return $part->descendants->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound])->count() == 0;
    }

    public function singleCheck(Part|ParsedPart $part, Check $check, ?string $filename = null): array
    {
        $this->runChecks($part, [$check], $filename);
        return $this->getErrors();
    }

    public function checkFile(Part|ParsedPart $part, ?string $filename = null): array
    {
        $this->runChecks($part, [
            new \App\LDraw\Check\Checks\LibraryApprovedName(),
            new \App\LDraw\Check\Checks\NameFileNameMatch(),
            new \App\LDraw\Check\Checks\UnknownPartNumber(),
            new \App\LDraw\Check\Checks\ValidBodyMeta(),
            new \App\LDraw\Check\Checks\ValidLines(),
            new \App\LDraw\Check\Checks\NoSelfReference()
        ], $filename);

        return $this->getErrors();
    }

    public function checkHeader(Part|ParsedPart $part): array
    {
        $this->runChecks($part, [
            new \App\LDraw\Check\Checks\MissingHeaderMeta(),
            new \App\LDraw\Check\Checks\LibraryApprovedDescription(),
            new \App\LDraw\Check\Checks\PatternPartDesciption(),
            new \App\LDraw\Check\Checks\AuthorInUsers(),
            new \App\LDraw\Check\Checks\NameAndPartType(),
            new \App\LDraw\Check\Checks\DescriptionModifier(),
            new \App\LDraw\Check\Checks\PhysicalColor(),
            new \App\LDraw\Check\Checks\AliasInParts(),
            new \App\LDraw\Check\Checks\FlexibleSectionIsPart(),
            new \App\LDraw\Check\Checks\FlexibleSectionName(),
            new \App\LDraw\Check\Checks\BfcIsCcw(),
            new \App\LDraw\Check\Checks\CategoryIsValid(),
            new \App\LDraw\Check\Checks\PatternHasSetKeyword(),
            new \App\LDraw\Check\Checks\HistoryIsValid(),
            new \App\LDraw\Check\Checks\PreviewIsValid(),
        ]);

        return $this->getErrors();
    }
}
