<?php

namespace App\LDraw\Parse;

use App\Models\Part;

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
    ) {}
    
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
            $cat = str_replace(['~','|','=','_'], '', mb_strstr($part->description, " ", true));
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
        foreach ($part->subparts as $s) {
            $n = str_replace('textures/', '', $s->filename);
            $n = str_replace(['parts/', 'p/'], '', $n);
            $subs[] = str_replace('/', '\\', $n);
        }
        $subs = array_unique($subs);

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
        );
    }
}