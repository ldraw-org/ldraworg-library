<?php

namespace App\LDraw\Check;

use App\LDraw\Check\Checks\LibraryApprovedLicense;
use App\LDraw\Check\Checks\HasRequiredHeaderMeta;
use App\LDraw\Check\Checks\LibraryApprovedName;
use App\LDraw\Check\Checks\NameFileNameMatch;
use App\LDraw\Check\Checks\UnknownPartNumber;
use App\LDraw\Check\Checks\ValidBodyMeta;
use App\LDraw\Check\Checks\ValidLines;
use App\LDraw\Check\Checks\ValidType1Lines;
use App\LDraw\Check\Checks\NoSelfReference;
use App\LDraw\Check\Checks\ValidType2Lines;
use App\LDraw\Check\Checks\ValidType3Lines;
use App\LDraw\Check\Checks\ValidType4Lines;
use App\LDraw\Check\Checks\ValidType5Lines;
use App\LDraw\Check\Checks\LibraryApprovedDescription;
use App\LDraw\Check\Checks\PatternPartDesciption;
use App\LDraw\Check\Checks\AuthorInUsers;
use App\LDraw\Check\Checks\NameAndPartType;
use App\LDraw\Check\Checks\DescriptionModifier;
use App\LDraw\Check\Checks\NewPartNotPhysicalColor;
use App\LDraw\Check\Checks\AliasInParts;
use App\LDraw\Check\Checks\FlexibleSectionIsPart;
use App\LDraw\Check\Checks\FlexibleHasCorrectSuffix;
use App\LDraw\Check\Checks\BfcIsCcw;
use App\LDraw\Check\Checks\CategoryIsValid;
use App\LDraw\Check\Checks\ObsoletePartIsValid;
use App\LDraw\Check\Checks\PatternHasSetKeyword;
use App\LDraw\Check\Checks\HistoryIsValid;
use App\LDraw\Check\Checks\HistoryUserIsRegistered;
use App\LDraw\Check\Checks\PreviewIsValid;
use App\Enums\CheckType;
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
    protected LibrarySettings $settings;
    protected PartCheckBag $errors;

    public function __construct(
        protected Part|ParsedPart $part
    ) {
        $this->settings = new LibrarySettings();
        $this->errors = new PartCheckBag();
    }

    public function runChecks(array $checks = [], ?string $filename = null): bool
    {
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
            $check->check($this->part, Closure::fromCallable([$this, 'add']));
            if ($this->errors->has(CheckType::Error) && property_exists($check, 'stopOnError') && $check->stopOnError === true) {
                break;
            }
        }
        return $this->errors->has(CheckType::Error);
    }

    public function add(PartError $error, array $context = []): void
    {
        $this->errors->add($error, $context);
    }

    public function get(CheckType|array|null $types = null, bool $translated = false): array
    {
        return $this->errors->get($types, $translated);
    }

    public function getPartCheckBag(): PartCheckBag
    {
        return $this->errors;
    }

    public function checkCanRelease(bool $checkFileErrors): bool
    {
        if ($this->part instanceof ParsedPart) {
            return false;
        }

        $this->errors->load($this->part->part_check->toArray());

        $this->errors->clear(CheckType::TrackerHold);

        if (!$this->part?->isTexmap() && $checkFileErrors) {
            $this->errors->clear(CheckType::Error);
            $this->errors->clear(CheckType::Warning);
            $this->standardChecks();
        }

        $this->errors->clear(CheckType::TrackerHold);
        $this->trackerChecks();
        return $this->errors->doesntHave(CheckType::holdable());
    }

    public function trackerChecks(): bool
    {
        $this->errors->clear(CheckType::TrackerHold);
        if ($this->part instanceof Part && $this->part->isUnofficial()) {
            $hascertparents = !is_null($this->part->official_part) ||
                $this->part->type->inPartsFolder() || $this->part->type == PartType::Helper ||
                $this->hasCertifiedParentInParts($this->part);
            if (!$hascertparents) {
                $this->add(PartError::TrackerNoCertifiedParents);
            }
            if (!$this->hasAllSubpartsCertified($this->part)) {
                $this->add(PartError::TrackerHasUncertifiedSubfiles);
            }
            if (count($this->part->missing_parts) > 0) {
                $this->add(PartError::TrackerHasMissingSubfiles);
            }
            if ($this->part->manual_hold_flag) {
                $this->add(PartError::TrackerAdminHold);
            }
            if ($this->part->license !== License::CC_BY_4) {
                $this->singleCheck(new LibraryApprovedLicense());
            }
        }
        return $this->errors->has(CheckType::holdable());
    }

    protected function hasCertifiedParentInParts(): bool
    {
        return Part::withQueryConstraint(
            fn ($query) =>
                $query->whereIn('parts.part_status', [PartStatus::Certified, PartStatus::Official]),
            fn () =>
                $this->part->ancestors()
        )
        ->whereIn('type', PartType::partsFolderTypes())
        ->exists();
    }

    protected function hasAllSubpartsCertified(): bool
    {
        return $this->part->descendants()
            ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound])
            ->doesntExist();
    }

    public function singleCheck(Check $check, ?string $filename = null): array
    {
        $this->runChecks([$check], $filename);
        return $this->errors->get(CheckType::holdable(), true);
    }

    public function standardChecks(?string $filename = null): bool
    {
        $this->runChecks([
            new HasRequiredHeaderMeta(),

            new LibraryApprovedName(),
            new NameFileNameMatch(),
            new UnknownPartNumber(),
            new ValidBodyMeta(),
            new ValidLines(),
            new ValidType1Lines(),
            new NoSelfReference(),
            new ValidType2Lines(),
            new ValidType3Lines(),
            new ValidType4Lines(),
            new ValidType5Lines(),

            new LibraryApprovedDescription(),
            new PatternPartDesciption(),
            new AuthorInUsers(),
            new NameAndPartType(),
            new DescriptionModifier(),
            new NewPartNotPhysicalColor(),
            new AliasInParts(),
            new FlexibleSectionIsPart(),
            new FlexibleHasCorrectSuffix(),
            new BfcIsCcw(),
            new CategoryIsValid(),
            new ObsoletePartIsValid(),
            new PatternHasSetKeyword(),
            new HistoryIsValid(),
            new HistoryUserIsRegistered(),
            new PreviewIsValid(),
        ], $filename);

        return $this->errors->has(CheckType::holdable());
    }
}
