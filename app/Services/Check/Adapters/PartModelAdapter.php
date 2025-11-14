<?php

namespace App\Services\Check\Adapters;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Enums\License;
use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Models\User;
use App\Services\Check\Contracts\PartDataAdapter;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PartModelAdapter implements PartDataAdapter
{
    protected ?ParsedPartCollection $parsedContent = null;
  
    public function __construct(
        protected Part $part
    )
    {}

    protected function parsedContent(): ParsedPartCollection
    {
        if (is_null($this->parsedContent)) {
            $this->parsedContent = new ParsedPartCollection($this->part->get());
        }
        return $this->parsedContent;
    }
  
    public function description(): ?string
    {
        return $this->part->description;
    }

    public function descriptionPrefix(): ?string
    {
        $pattern = '#^(?:(?P<prefix>[~_=|]+)\h*)?.*?$#u';
        preg_match($pattern, $this->part->description, $matches);
        return Arr::get($matches, 'prefix');
    }

    public function name(): ?string
    {
        return $this->part->meta_name;
    }

    public function isPattern(): bool
    {
        return $this->parsedContent()->isPattern();
    }

    public function lastSuffixStartsWith(string $letter): bool
    {
        return $this->parsedContent()->lastSuffixStartsWith($letter);
    }

    public function author(): ?User
    {
        return $this->part->user;
    }
    
    public function type(): ?PartType
    {
        return $this->part->type;
    }

    public function type_qualifier(): ?PartTypeQualifier
    {
        return $this->part->type_qualifier;
    }

    public function license(): ?License
    {
        return $this->part->license;
    }    

    public function bfc(): ?string
    {
        return $this->part->bfc;
    }

    public function category(): ?PartCategory
    {
        return $this->part->category;
    }    

    public function keywords(): array
    {
        return $this->part->keywords
            ->pluck('keyword')
            ->values()
            ->all();
    }

    public function preview(): ?array
    {
        return $this->parsedContent()->where('meta', 'preview')->where('invalid', false)->first();
    }

    public function invalidLines(): Collection
    {
        return $this->parsedContent()
            ->where('invalid', true)
            ->sortby('line_number');
    }

    public function bodyLines(): Collection
    {
        return $this->parsedContent()
            ->bodyLines()
            ->where('invalid', false)
            ->sortby('line_number');
    }

    public function subparts(): array
    {
        return $this->part->subparts
            ->pluck('meta_name')
            ->values()
            ->all();
    }

    public function history(): array
    {
        return $this->part->history
            ->map(fn (PartHistory $history) => [
                    'date' => $history->created_at->format('Y-m-d'),
                    'username' => $history->user->is_legacy ? null : $history->user->name,
                    'realname' => $history->user->is_legacy ? $history->user->realname : null,
                    'comment' => $history->comment,
                ])
            ->values()
            ->all();
    }

    public function rawPart(): Part
    {
        return $this->part;
    }
}