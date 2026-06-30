<?php

namespace App\Services\Check\Contracts;

use App\Services\Check\Enums\CheckType;

interface CheckItem
{
    public function type(): CheckType;
    public function isMultiLine(): bool;
    public function multiLineHeader(): ?string;

    public function description(): string;
    public function label(): string;
    public function message(): string;

}
