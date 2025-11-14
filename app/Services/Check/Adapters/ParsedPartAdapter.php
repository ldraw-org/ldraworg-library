<?php

namespace App\Services\Check\Adapters;

use App\Enums\PartType;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartTypeQualifier;
use App\Models\User;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Check\Contracts\PartDataAdapter;
use Illuminate\Support\Collection;

class ParsedPartAdapter implements PartDataAdapter
{
    public function __construct(
        protected ParsedPartCollection $part
    )
    {}

    public function description(): ?string
    {
        return $this->part->description();
    }

    public function descriptionPrefix(): ?string
    {
        return $this->part->descriptionPrefix();
    }

    public function name(): ?string
    {
        return $this->part->name();
    }

    public function isPattern(): bool
    {
        return $this->part->isPattern();
    }

    public function lastSuffixStartsWith(string $letter): bool
    {
        return $this->part->lastSuffixStartsWith($letter);
    }

    public function author(): ?User
    {
        return $this->part->authorUser();
    }
    
    public function type(): ?PartType
    {
        return $this->part->type();
    }

    public function type_qualifier(): ?PartTypeQualifier
    {
        return $this->part->type_qualifier();
    }

    public function license(): ?License
    {
        return $this->part->license();
    }    

    public function bfc(): ?string
    {
        return $this->part->headerBfc();
    }

    public function category(): ?PartCategory
    {
        return $this->part->category();
    }    

    public function keywords(): array
    {
        return $this->part->keywords() ?? [];
    }

    public function hasInvalidPreview(): bool
    {
        return $this->part->hasInvalidPreview();
    }

    public function preview(): ?array
    {
        return $this->part->where('meta', 'preview')->where('invalid', false)->first();
    }

    public function invalidLines(): Collection
    {
        return $this->part
            ->where('invalid', true)
            ->sortby('line_number');
    }

    public function bodyLines(): Collection
    {
        return $this->part->bodyLines()
            ->where('invalid', false)
            ->sortby('line_number');
    }

    public function subparts(): array
    {
        return $this->part->subparts() ?? [];
    }

    public function history(): array
    {
        return $this->part->history() ?? [];
    }

    public function hasInvalidHistory(): bool
    {
        return $this->part->hasInvalidHistory();
    }

}