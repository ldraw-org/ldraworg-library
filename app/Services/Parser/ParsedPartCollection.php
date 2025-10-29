<?php

namespace App\Services\Parser;

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ParsedPartCollection extends Collection
{
    public function __construct($items = [])
    {
        if (is_string($items)) {
            parent::__construct(app(ImprovedParser::class)->parse($items));
        } else {
            parent::__construct($items);
        }
    }

    public function getFirstMeta(string $meta, bool $validOnly = true): ?array
    {
        return $this
            ->when($validOnly,
                fn (Collection $collection, bool $value) => $collection->where('invalid', false)
            )
            ->where('meta', $meta)
            ->sortBy('line_number')->first();
    }
  
    public function description(): ?string
    {
        return Arr::get($this->getFirstMeta('description') ?? [], 'description');
    }

    public function descriptionPrefix(): ?string
    {
        return Arr::get($this->getFirstMeta('description') ?? [], 'prefix');
    }

    public function name(): ?string
    {
        return Arr::get($this->getFirstMeta('name') ?? [], 'name');
    }
  
    public function author(): ?array
    {
        $author = $this->getFirstMeta('author');
        if (is_null($author)) {
            return null;
        }
      
        $username = Arr::get($author, 'username');
        $realname = Arr::get($author, 'realname');

        $username = $username == '' ? null : $username;
        $realname = $realname == '' ? null : $realname;
      
        if (is_null($username) && is_null($realname)) {
            return null;
        }        
        return compact('realname', 'username');        
    }

    public function authorUser(): ?User
    {
        $author = $this->author();

        if (is_null($author)) {
            return null;
        }
        return User::firstWhere(fn (Builder $query) => $query->orWhere('name', $author['username'])->orWhere('realname', $author['realname']));
    }

    public function type(): ?PartType
    {
        return PartType::tryFrom(Arr::get($this->getFirstMeta('ldraworg') ?? [], 'type'));  
    }

    public function type_qualifier(): ?PartTypeQualifier
    {
        return PartTypeQualifier::tryFrom(Arr::get($this->getFirstMeta('ldraworg') ?? [], 'type_qualifier'));  
    }

    public function license(): ?License
    {
        return License::tryFromText(Arr::get($this->getFirstMeta('license') ?? [], 'license', ''));
    }

    public function help(): ?array
    {
        $help = $this
            ->where('meta', 'help')
            ->where('invalid', false)
            ->sortBy('line_number')
            ->pluck('help')
            ->map(fn (string $h) => trim($h))
            ->filter()
            ->values()
            ->all();
        return count($help) ? $help : null;
    }

    public function bfc(): ?array
    {
        $bfc = $this
            ->where('meta', 'bfc')
            ->where('invalid', false)
            ->map(fn (array $bfc) => [
                'line_number' => Arr::get($bfc, 'line_number'),
                'in_header' => Arr::get($bfc, 'line_number') < $this->bodyStartLine(),
                'command' => Arr::get($bfc, 'bfc'),
                'winding' => Arr::get($bfc, 'winding'),
            ])
            ->sortBy('line_number')
            ->values()
            ->all();
        return count($bfc) ? $bfc : null;
    }

    public function headerBfc(): ?string
    {
        $bfc = $this->headerLines()->getFirstMeta('bfc');
        if (is_null($bfc)) {
            return null;
        }
        return Arr::get($bfc, 'winding') ?? 'none';
    }

    public function category(): ?PartCategory
    {
        $meta = $this->getFirstMeta('category', false);
        if (!is_null($meta) && Arr::get($meta, 'invalid') === true) {
            return null;
        } elseif(!is_null($meta)) {
            return PartCategory::tryFrom(Arr::get($meta, 'category'));
        }

        return PartCategory::tryFrom(Arr::get($this->getFirstMeta('description'), 'category') );
    }

    public function keywords(): ?array
    {
        $keywords = $this
            ->where('meta', 'keywords')
            ->where('invalid', false)
            ->pluck('keywords')
            ->map(fn (string $keywords) => explode(',', $keywords))
            ->flatten()
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
        return count($keywords) ? $keywords : null;
    }

    public function cmdline(): ?string
    {
        return Arr::get($this->getFirstMeta('cmdline') ?? [], 'cmdline');
    }

    public function preview(): ?string
    {
        $preview = $this->getFirstMeta('preview');
        if (is_null($preview)) {
            return null;
        }
        
        return "{$preview['color']} " .
            "{$preview['x1']} {$preview['y1']} {$preview['z1']} " .
            "{$preview['a']} {$preview['b']} {$preview['c']} " . 
            "{$preview['d']} {$preview['e']} {$preview['f']} " .
            "{$preview['g']} {$preview['h']} {$preview['i']}";
    }

    public function history(): ?array
    {
        $history = $this
            ->where('meta', 'history')
            ->where('invalid', false)
            ->map(fn (array $hist) => [
                'date' => Arr::get($hist, 'date'),
                'realname' => Arr::get($hist, 'realname'),
                'username' => Arr::get($hist, 'username'),
                'comment' => Arr::get($hist, 'comment'),
            ])
            ->sortBy('date')
            ->values()
            ->all();
        return count($history) ? $history : null;
    }

    public function subparts(): ?array
    {
        $subparts = $this
            ->pluck('file')
            ->merge($this->pluck('glossfile'), $this->pluck('tex_geometry.line.file'))
            ->filter()
            ->map(fn (string $subpart) => Str::of($subpart)->lower()->trim()->toString())
            ->unique()
            ->sort()
            ->values()
            ->all();
        return count($subparts) ? $subparts : null;
    }

    public function hasInvalidLines(): bool
    {
        return $this->isNotEmpty() &&
            $this->where('invalid', true)->isNotEmpty();
    }

    public function allLinesValid(): bool
    {
        return $this->where('invalid', true)->isEmpty();
    }
  
    public function invalidLineNumbers(): array
    {
        return $this
            ->where('invalid', true)
            ->pluck('line_number')
            ->values()
            ->all();
    }

    public function hasInvalidHistory(): bool
    {
        return $this->where('meta', 'history')->isNotEmpty() &&
            $this->where('meta', 'history')->where('invalid', true)->isNotEmpty();
    }

    public function hasInvalidPreview(): bool
    {
        return $this->where('meta', 'preview')->isNotEmpty() &&
            $this->where('meta', 'preview')->where('invalid', true)->isNotEmpty();
    }

    public function bodyStartLine(): int
    {
        return $this
                ->where('linetype', '!=', 'blank')
                ->whereNotIn('meta', [
                    'description',
                    'name',
                    'author',
                    'ldraworg',
                    'license',
                    'help',
                    'category',
                    'keywords',
                    'cmdline',
                    'preview',
                    'history',
                ])
                ->whereNotIn('bfc', ['CERTIFY', 'NOCERTIFY'])
                ->min('line_number') ?? ($this->max('line_number') ?? 1) + 1;
    }

    public function headerLines(): self
    {
        return $this
            ->where('line_number', '<', $this->bodyStartLine());
    }

    public function headerText(): string
    {
        return $this
            ->headerLines()
            ->pluck('text')
            ->implode("\n") ?? '';
    }

    public function bodyLines(): self
    {
        return $this
            ->where('line_number', '>=', $this->bodyStartLine());
    }

    public function bodyText(): string
    {
        return $this
            ->bodyLines()
            ->pluck('text')
            ->implode("\n") ?? '';
    }

    public function lastSuffixStartsWith(string $letter): bool
    {
        $nameRaw = $this->getFirstMeta('name');
        if (is_null($nameRaw) || is_null(Arr::get($nameRaw, 'suffixes')) || Arr::get($nameRaw, 'suffixes_invalid') === true) {
           return false;
        }

        $suffixes = Arr::get($nameRaw, 'suffixes');
        $suffix_num = count($suffixes);
        if ($suffix_num == 1) {
            return  Str::startsWith($suffixes[0], $letter);
        } elseif (Str::startsWith($suffixes[$suffix_num-1], '-')) {
            return Str::startsWith($suffixes[$suffix_num-2], $letter);
        }
        return  Str::startsWith($suffixes[$suffix_num-1], $letter);    
    }
  
    public function isPattern(): bool
    {
        return $this->type()?->inPartsFolder() && $this->lastSuffixStartsWith('p');
    }

    public function isComposite(): bool
    {
        return $this->type()?->inPartsFolder() && $this->lastSuffixStartsWith('c');
    }

}