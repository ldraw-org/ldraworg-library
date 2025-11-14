<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\PartOnly;

class TrackerHolds extends BaseCheck
{
    use PartOnly;
  
    public function check(): iterable
    {
        if ($this->part->rawPart()->isOfficial()) {
            return;
        }
      
        $hasCertifiedParents = $this->hasCertifiedParents();
        
        // Check for uncertified subparts
        $hasUncertifiedSubparts = $this->hasUncertifiedSubparts();
        
        // Validate and yield errors
        if (!$hasCertifiedParents) {
            yield $this->error(CheckType::TrackerHold, PartError::TrackerNoCertifiedParents);
        }
        
        if ($hasUncertifiedSubparts) {
            yield $this->error(CheckType::TrackerHold, PartError::TrackerHasUncertifiedSubfiles);
        }
        
        if ($this->hasMissingSubfiles()) {
            yield $this->error(CheckType::TrackerHold, PartError::TrackerHasMissingSubfiles);
        }
        
        if ($this->part->rawPart()->manual_hold_flag) {
            yield $this->error(CheckType::TrackerHold, PartError::TrackerAdminHold);
        }
    }

    private function hasCertifiedParents(): bool
    {
        $rawPart = $this->part->rawPart();
        
        if ($rawPart->official_part !== null) {
            return true;
        }
        
        if ($rawPart->type->inPartsFolder() || $rawPart->type === PartType::Helper) {
            return true;
        }
        
        return $rawPart->ancestors()
            ->whereIn('part_status', [PartStatus::Certified, PartStatus::Official])
            ->whereIn('type', PartType::partsFolderTypes())
            ->exists();
    }
    
    private function hasUncertifiedSubparts(): bool
    {
        $uncertifiedStatuses = [
            PartStatus::AwaitingAdminReview,
            PartStatus::Needs2MoreVotes,
            PartStatus::Needs1MoreVote,
            PartStatus::ErrorsFound,
        ];
        
        return $this->part->rawPart()
            ->descendants()
            ->whereIn('part_status', $uncertifiedStatuses)
            ->exists();
    }
    
    private function hasMissingSubfiles(): bool
    {
        return count($this->part->rawPart()->missing_parts) > 0;
    }
}
