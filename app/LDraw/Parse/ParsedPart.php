<?php

namespace App\LDraw\Parse;

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use Illuminate\Support\Str;

class ParsedPart
{
    public function __construct(
        public ?string $description = null,
        public ?string $name = null,
        public ?string $username = null,
        public ?string $realname = null,
        public ?bool $unofficial = null,
        public ?PartType $type = null,
        public ?PartTypeQualifier $type_qualifier = null,
        public ?string $releasetype = null,
        public ?string $release = null,
        public ?License $license = null,
        public ?array $help = null,
        public ?string $bfc = null,
        public ?PartCategory $metaCategory = null,
        public ?PartCategory $descriptionCategory = null,
        public ?array $keywords = null,
        public ?string $cmdline = null,
        public ?string $preview = null,
        public ?array $history = null,
        public ?array $subparts = null,
        public ?string $body = null,
        public ?string $rawText = null,
        public int $header_length = 0,
    ) {
    }

    public static function fromArray(array $attributes): self
    {
        $p = new self();
        foreach ($attributes as $attribute => $value) {
            if (property_exists($p, $attribute)) {
                $p->{$attribute} = $value;
            }
        }
        return $p;
    }

    public static function fromPart(Part $part): self
    {
        if (!is_null($part->release) && $part->release->name == 'original') {
            $releasetype = 'original';
        } elseif (!is_null($part->release)) {
            $releasetype = 'update';
        } else {
            $releasetype = '';
        }
        if (!is_null($part->category)) {
            $word = 1;
            if (Str::of($part->description)->trim()->words(1, '')->replace(['~', '|', '=', '_'], '') == '') {
                $word = 2;
            }
            $cat = Str::of($part->description)->trim()->words($word, '')->replace(['~', '|', '=', '_', ' '], '')->toString();
            $cat = PartCategory::tryFrom($cat);
            if ($cat != $part->category) {
                $descriptionCategory = null;
                $metaCategory = $part->category;
            } else {
                $descriptionCategory = $part->category;
                $metaCategory = null;
            }
        }
        $history = [];
        foreach ($part->history as $h) {
            $history[] = [
                'user' => $h->user->name,
                'date' => date_format(date_create($h->created_at), "Y-m-d"),
                'comment' => $h->comment
            ];
        }
        $subs = [];
        $textures = [];
        foreach ($part->subparts as $s) {
            /** @var Part $s */
            if ($s->isTexmap()) {
                $textures[] = str_replace('textures/', '', $s->name());
            } else {
                $subs[] = $s->name();
            }
        }
        $subs = ['subparts' => array_unique($subs), 'textures' => array_unique($textures)];

        return new self(
            $part->description,
            $part->name(),
            $part->user->name,
            $part->user->realname,
            is_null($part->release),
            $part->type,
            $part->type_qualifier,
            $releasetype,
            $part->release?->short,
            $part->license,
            $part->help->pluck('text')->all(),
            $part->bfc,
            $metaCategory ?? null,
            $descriptionCategory ?? null,
            $part->keywords->pluck('keyword')->all(),
            $part->cmdline,
            $part->preview,
            $history,
            $subs,
            $part->body->body,
            $part->get(),
            count(explode("\n", $part->header)) + 2
        );
    }
}
