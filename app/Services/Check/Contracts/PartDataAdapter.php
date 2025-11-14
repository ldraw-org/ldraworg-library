<?php

namespace App\Services\Check\Contracts;

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Models\User;
use Illuminate\Support\Collection;

interface PartDataAdapter
{
    public function description(): ?string;
    public function descriptionPrefix(): ?string;
    public function name(): ?string;
    public function isPattern(): bool;
    public function lastSuffixStartsWith(string $letter): bool;
    public function author(): ?User;
    public function type(): ?PartType;
    public function type_qualifier(): ?PartTypeQualifier;
    public function license(): ?License;
    public function bfc(): ?string;
    public function category(): ?PartCategory;
    public function keywords(): array;
    public function preview(): ?array;
    public function history(): array;
    public function invalidLines(): Collection;
    public function bodyLines(): Collection;
    public function subparts(): array;
}
