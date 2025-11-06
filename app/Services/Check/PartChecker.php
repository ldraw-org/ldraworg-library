<?php

namespace App\Services\Check;

use App\Enums\License;
use App\Services\Check\PartChecks\HasRequiredHeaderMeta;
use App\Services\Check\PartChecks\LibraryApprovedName;
use App\Services\Check\PartChecks\UnknownPartNumber;
use App\Services\Check\PartChecks\ValidLines;
use App\Services\Check\PartChecks\NoSelfReference;
use App\Services\Check\PartChecks\LibraryApprovedDescription;
use App\Services\Check\PartChecks\PatternPartDescription;
use App\Services\Check\PartChecks\AuthorInUsers;
use App\Services\Check\PartChecks\NameAndPartType;
use App\Services\Check\PartChecks\DescriptionModifier;
use App\Services\Check\PartChecks\NewPartNotPhysicalColor;
use App\Services\Check\PartChecks\FlexibleSectionIsPart;
use App\Services\Check\PartChecks\FlexibleHasCorrectSuffix;
use App\Services\Check\PartChecks\CategoryIsValid;
use App\Services\Check\PartChecks\ObsoletePartIsValid;
use App\Services\Check\PartChecks\PatternHasSetKeyword;
use App\Services\Check\PartChecks\HistoryIsValid;
use App\Services\Check\PartChecks\HistoryUserIsRegistered;
use App\Services\Check\PartChecks\PreviewIsValid;

use App\Services\Check\PartChecks\QuadNotCoplanarWarning;
use App\Services\Check\PartChecks\MinifigCategoryWarning;
use App\Services\Check\PartChecks\StickerColorWarning;

use App\Enums\PartError;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Check\Contracts\Check;
use App\Services\Check\Contracts\FilenameAwareCheck;
use App\Services\Check\Contracts\SettingsAwareCheck;
use App\Services\Check\PartChecks\AliasInParts;
use App\Services\Check\PartChecks\BfcIsCcw;
use App\Services\Check\PartChecks\LibraryApprovedLicense;
use App\Services\Check\PartChecks\LibraryLicenseWarning;
use App\Services\Parser\ParsedPartCollection;
use App\Settings\LibrarySettings;
use Illuminate\Support\Collection;

class PartChecker
{
    protected Collection $messages;
    protected ParsedPartCollection $part;
    protected ?Part $libraryPart = null;
    protected LibrarySettings $settings;
  
    public function __construct(ParsedPartCollection|Part $part) {
        $this->messages = new Collection();
        $this->settings = app(LibrarySettings::class);
        $this->setPart($part);
    }

    public function setPart(ParsedPartCollection|Part $part) {
        if ($part instanceof Part) {
            $this->libraryPart = $part;
            $text = $part->isTexmap() ? '' : $part->get();
            $this->part = new ParsedPartCollection($text);
        } else {
            $this->part = $part;
        }      
    }
  
    public function runChecks(Check|array $checks, ?string $filename = null): void
    {
        if (!is_array ($checks)) {
            $checks = [$checks];
        }
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
            $check->check($this->part, \Closure::fromCallable([$this, 'add']));
            if ($this->messages->isNotEmpty() && property_exists($check, 'stopOnError') && $check->stopOnError === true) {
                break;
            }
        }
    }

    public function add(PartError $error, ?int $lineNumber = null, ?string $value = null, ?string $type = null, ?string $text = null): void
    {
        $this->messages->push(CheckMessage::fromArray(compact(['error', 'lineNumber', 'value', 'type', 'text'])));
    }

    public static function singleCheck(ParsedPartCollection|Part $part, Check $check, ?string $filename = null): Collection
    {
        if ($part instanceof Part) {
            $text = $part->isTexmap() ? '' : $part->get();
            $part = new ParsedPartCollection($text);
        }      
        if ($check instanceof FilenameAwareCheck) {
            $check->setFilename($filename);
        }
        if ($check instanceof SettingsAwareCheck) {
            $check->setSettings(app(LibrarySettings::class));
        }
        $message = new Collection();
        $add = function (PartError $error, ?int $lineNumber = null, ?string $value = null, ?string $type = null, ?string $text = null) use (&$message) {
            $message->push(CheckMessage::fromArray(compact(['error', 'lineNumber', 'value', 'type', 'text'])));
        };
        $check->check($part, \Closure::fromCallable($add));
        return $message;
    }

    public function errorCheck(?string $filename = null): Collection
    {
        $this->messages = new Collection();
        $this->runChecks([
            new HasRequiredHeaderMeta(),

            new LibraryApprovedName(),
            new UnknownPartNumber(),
            new ValidLines(),
            new NoSelfReference(),

            new LibraryApprovedDescription(),
            new LibraryApprovedLicense(),
            new PatternPartDescription(),
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
        return $this->messages;
    }

    protected function hasCertifiedParentInParts(): bool
    {
        return Part::withQueryConstraint(
            fn ($query) =>
                $query->whereIn('parts.part_status', [PartStatus::Certified, PartStatus::Official]),
            fn () =>
                $this->libraryPart->ancestors()
        )
        ->whereIn('type', PartType::partsFolderTypes())
        ->exists();
    }

    protected function hasAllSubpartsCertified(): bool
    {
        return $this->libraryPart->descendants()
            ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::Needs2MoreVotes, PartStatus::Needs1MoreVote, PartStatus::ErrorsFound])
            ->doesntExist();
    }

    public function trackerChecks(): Collection
    {
        $tracker_errors = new Collection();
        if (!is_null($this->libraryPart) && $this->libraryPart->isUnofficial()) {
            $hascertparents = !is_null($this->libraryPart->official_part) ||
                $this->libraryPart->type->inPartsFolder() || $this->libraryPart->type == PartType::Helper ||
                $this->hasCertifiedParentInParts();
            if (!$hascertparents) {
                $tracker_errors->push(CheckMessage::fromPartError(PartError::TrackerNoCertifiedParents));
            }
            if (!$this->hasAllSubpartsCertified()) {
                $tracker_errors->push(CheckMessage::fromPartError(PartError::TrackerHasUncertifiedSubfiles));
            }
            if (count($this->libraryPart->missing_parts) > 0) {
                $tracker_errors->push(CheckMessage::fromPartError(PartError::TrackerHasMissingSubfiles));
            }
            if ($this->libraryPart->manual_hold_flag) {
                $tracker_errors->push(CheckMessage::fromPartError(PartError::TrackerAdminHold));
            }
        }
        return $tracker_errors;
    }

    public function warningChecks(): Collection
    {
        $this->messages = new Collection();
        $checks = [
            new QuadNotCoplanarWarning(),
            new StickerColorWarning(),          
        ];
        if (!is_null($this->libraryPart) && $this->libraryPart->isUnofficial()) {
            $checks[] = new MinifigCategoryWarning();
            $checks[] = new LibraryLicenseWarning();          
        }
        $this->runChecks($checks);
        return $this->messages;
    }

}