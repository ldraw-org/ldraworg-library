<?php

namespace App\Services\Check;

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

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Check\Contracts\FilenameAwareCheck;
use App\Services\Check\Contracts\SettingsAwareCheck;
use App\Services\Check\PartChecks\AliasInParts;
use App\Services\Check\PartChecks\BfcIsCcw;
use App\Services\Check\PartChecks\LibraryApprovedLicense;
use App\Services\Parser\ParsedPartCollection;
use App\Settings\LibrarySettings;
use Illuminate\Support\Collection;

class PartChecker
{
    protected Collection $messages;
    protected ParsedPartCollection $part;
  
    public function __construct(
        protected LibrarySettings $settings
    ) {
       $this->messages = new Collection();
    }
  
    public function runChecks(array $checks = [], ?string $filename = null): void
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
            $check->check($this->part, \Closure::fromCallable([$this, 'add']));
            if ($this->messages->isNotEmpty() && property_exists($check, 'stopOnError') && $check->stopOnError === true) {
                break;
            }
        }
    }

    public function add(PartError $error, ?int $lineNumber = null, ?string $value = null, ?string $type = null): void
    {
        $this->messages->push(CheckMessage::fromArray(compact(['error', 'lineNumber', 'value', 'type'])));
    }

    public function singleCheck(ParsedPartCollection $part, Check $check, ?string $filename = null): Collection
    {
        $this->part = $part;
        $this->messages = new Collection();
        $this->runChecks([$check], $filename);
        return $this->messages;
    }

    public function check(ParsedPartCollection $part, ?string $filename = null): Collection
    {
        $this->part = $part;
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
}