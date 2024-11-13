<?php

namespace App\LDraw\Parse;

use App\Models\Part\Part;

class ParsedPart
{
    public function __construct(
        public ?string $description,
        public ?string $name,
        public ?string $username,
        public ?string $realname,
        public ?bool $unofficial,
        public ?string $type,
        public ?string $qual,
        public ?string $releasetype,
        public ?string $release,
        public ?string $license,
        public ?array $help,
        public ?string $bfcwinding,
        public ?string $metaCategory,
        public ?string $descriptionCategory,
        public ?array $keywords,
        public ?string $cmdline,
        public ?array $history,
        public ?array $subparts,
        public ?string $body,
        public ?string $rawText,
        public int $header_length = 0,
    ) {
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
            $d = trim($part->description);
            if ($d !== '' && in_array($d[0], ['~', '|', '=', '_'])) {
                $d = trim(substr($d, 1));
            }
            $cat = mb_strstr($d, " ", true);
            if ($cat != $part->category->category) {
                $metaCategory = $part->category->category;
            } else {
                $descriptionCategory = $part->category->category;
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
            $part->type->type,
            $part->type_qualifier->type ?? null,
            $releasetype,
            $part->release->short ?? null,
            $part->license->text,
            $part->help->pluck('text')->all(),
            $part->bfc,
            $metaCategory ?? null,
            $descriptionCategory ?? null,
            $part->keywords->pluck('keyword')->all(),
            $part->cmdline,
            $history,
            $subs,
            $part->body->body,
            $part->get(),
            count(explode("\n", $part->header)) + 2
        );
    }
}
