<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Enums\CheckType;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\ParsedPartOnly;

class HasRequiredHeaderMeta extends BaseCheck
{
    use ParsedPartOnly;
  
    public bool $stopOnError = true;

    public function check(): iterable
    {
        if (is_null($this->part->description())) {
            yield $this->error(CheckType::Error, PartError::MissingHeaderMeta, value: 'Description');
        }
        if (is_null($this->part->author())) {
            yield $this->error(CheckType::Error, PartError::AuthorInvalid);
        }
        if (is_null($this->part->name())) {
            yield $this->error(CheckType::Error, PartError::MissingHeaderMeta, value: 'Name:');
        }
        if (is_null($this->part->type())) {
            yield $this->error(CheckType::Error, PartError::MissingHeaderMeta, value: '!LDRAW_ORG');
        }
        if (is_null($this->part->license())) {
            yield $this->error(CheckType::Error, PartError::MissingHeaderMeta, value: '!LICENSE');
        }
    }
}
