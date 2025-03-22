<?php

namespace App\LDraw\Check;

use App\Enums\License;
use App\Enums\PartError;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Check\Contracts\FilenameAwareCheck;
use App\LDraw\Check\Contracts\SettingsAwareCheck;
use App\Models\Part\Part;
use App\LDraw\Parse\ParsedPart;
use App\Settings\LibrarySettings;
use Closure;

class PartChecker
{
    protected ErrorCheckBag $errors;

    public function __construct(
        protected LibrarySettings $settings
    ) {
        $this->errors = new ErrorCheckBag();
    }

    public function runChecks(Part|ParsedPart $part, array $checks = [], ?string $filename = null): bool {
        $this->errors = new ErrorCheckBag();
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
            if ($this->hasErrors() && property_exists($check, 'stopOnError') && $check->stopOnError === true) {
                break;
            }
        }
        return $this->hasErrors();
    }

    public function addError(PartError $error, array $context = []): void
    {
        $this->errors->add($error, $context);
    }

    public function hasErrors(): bool
    {
        return !$this->errors->isEmpty();
    }

    public function getErrors(): array
    {
        return $this->errors->getErrors();
    }

    public function getErrorStorageArray(): array
    {
        return $this->errors->toArray();
    }

    public function checkCanRelease(Part $part): bool
    {
        $errors = [];

        if (!$part->isTexmap()) {
            $this->standardChecks($part);
        }

        if ($part->isUnofficial()) {
            $hascertparents = !is_null($part->official_part) ||
                $part->type->inPartsFolder() || $part->type == PartType::Helper ||
                $this->hasCertifiedParentInParts($part);
            if (!$hascertparents) {
                $this->addError(PartError::NoCertifiedParents);
            }
            if (!$this->hasAllSubpartsCertified($part)) {
                $this->addError(PartError::HasUncertifiedSubfiles);
            }
            if (count($part->missing_parts) > 0) {
                $this->addError(PartError::HasMissingSubfiles);
            }
            if ($part->manual_hold_flag) {
                $this->addError(PartError::AdminHold);
            }
            if ($part->license !== License::CC_BY_4) {
                $this->addError(PartError::LicenseNotLibraryApproved, ['license' => $part->license->value]);
            }
        }
        $errors = $this->getErrors();
        return count($errors) == 0;
    }

    public function hasCertifiedParentInParts(Part $part): bool
    {
        return Part::withQueryConstraint(
            fn ($query) =>
                $query->whereIn('parts.part_status', [PartStatus::Certified, PartStatus::Official]),
            fn () =>
                $part->ancestors()
        )
        ->whereIn('type', PartType::partsFolderTypes())
        ->exists();
    }

    public function hasAllSubpartsCertified(Part $part): bool
    {
        return !$part->descendants()
            ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound])
            ->exists();
    }

    public function singleCheck(Part|ParsedPart $part, Check $check, ?string $filename = null): array
    {
        $this->runChecks($part, [$check], $filename);
        return $this->getErrors();
    }

    public function standardChecks(Part|ParsedPart $part, ?string $filename = null): bool
    {
        $this->runChecks($part, [
            new \App\LDraw\Check\Checks\HasRequiredHeaderMeta(),

            new \App\LDraw\Check\Checks\LibraryApprovedName(),
            new \App\LDraw\Check\Checks\NameFileNameMatch(),
            new \App\LDraw\Check\Checks\UnknownPartNumber(),
            new \App\LDraw\Check\Checks\ValidBodyMeta(),
            new \App\LDraw\Check\Checks\ValidLines(),
            new \App\LDraw\Check\Checks\NoSelfReference(),

            new \App\LDraw\Check\Checks\LibraryApprovedDescription(),
            new \App\LDraw\Check\Checks\PatternPartDesciption(),
            new \App\LDraw\Check\Checks\AuthorInUsers(),
            new \App\LDraw\Check\Checks\NameAndPartType(),
            new \App\LDraw\Check\Checks\DescriptionModifier(),
            new \App\LDraw\Check\Checks\NewPartNotPhysicalColor(),
            new \App\LDraw\Check\Checks\AliasInParts(),
            new \App\LDraw\Check\Checks\FlexibleSectionIsPart(),
            new \App\LDraw\Check\Checks\FlexibleHasCorrectSuffix(),
            new \App\LDraw\Check\Checks\BfcIsCcw(),
            new \App\LDraw\Check\Checks\CategoryIsValid(),
            new \App\LDraw\Check\Checks\PatternHasSetKeyword(),
            new \App\LDraw\Check\Checks\HistoryIsValid(),
            new \App\LDraw\Check\Checks\HistoryUserIsRegistered(),
            new \App\LDraw\Check\Checks\PreviewIsValid(),
        ], $filename);

        return $this->hasErrors();
    }
}
