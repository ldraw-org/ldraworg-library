<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartWarning;
use App\Services\Check\Traits\ParsedPartOnly;

class PreviewIsValid extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if ($this->part->hasInvalidPreview() || ($this->part->preview() !== null && $this->part->previewRotation() === null)) {
            yield $this->error(PartWarning::WarningPreviewInvalid);
        }
    }
}
