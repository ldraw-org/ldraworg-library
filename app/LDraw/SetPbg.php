<?php

namespace App\LDraw;

use App\Enums\PartCategory;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\StickerSheet;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

class SetPbg
{
    public MessageBag $messages;
    public array $parts = [];
    protected array $set = [];
    protected Rebrickable $rb;

    public function __construct(?string $set_number = null)
    {
        $this->rb = new Rebrickable();
        $this->messages = new MessageBag();
        if (!is_null($set_number)) {
            $this->set = $this->rb->getSet($set_number)->all();
        }
    }

    public function pbg(?string $set_number = null): string|false
    {

        if ((is_null($set_number) && Arr::exists($this->set, 'set_num')) ||
            (Arr::get($this->set, 'set_num') == $set_number)
            && count($this->parts) > 0) {
            return $this->makePbg();
        } elseif (is_null($set_number) && !Arr::exists($this->set, 'set_num')) {
            $this->messages = new MessageBag();
            $this->messages->add('errors', 'Set number empty');
            return false;
        }

        $this->parts = [];


        $this->set = $this->rb->getSet($set_number)->all();

        if (!Arr::exists($this->set, 'set_num')) {
            $this->messages->add('errors', 'Set Not Found');
            return false;
        }

        $rb_parts = $this->rb->getSetParts($set_number);
        $no_ldraw = $rb_parts->whereNull('part.external_ids.LDraw');
        if ($no_ldraw->whereNotNull('part.print_of')->count() > 0) {
            $unpatterned = $this->rb->getParts(['part_nums' => $no_ldraw->whereNotNull('part.print_of')->pluck('part.print_of')->all()]);
        } else {
            $unpatterned = new Collection();
        }
        $rb_parts->whereNull('part.external_ids.LDraw')
            ->whereNotNull('part.print_of')
            ->transform(function (array $part, int $key) use ($unpatterned) {
                $upart = $unpatterned->where('part_num', $part['part']['print_of'])
                    ->whereNotNull('external_ids.LDraw')
                    ->first();
                if (!is_null($upart)) {
                    $this->messages->add('unpatterned', "{$part['part']['part_num']} ({$upart['external_ids']['LDraw'][0]})");
                    $part['part'] = $upart;
                }
                return $part;
            });

        foreach ($rb_parts->whereNotNull('part.external_ids.LDraw') as $part) {
            $this->addPart($part);
        }

        foreach ($rb_parts->whereNull('part.external_ids.LDraw') as $part) {
            $p = Part::firstWhere('filename', 'parts/' . $part['part']['part_num'] . '.dat');
            $sticker_sheet = StickerSheet::where('rebrickable->part_num', $part['part']['part_num'])->first();
            if (!is_null($p)) {
                $this->addPart($part, basename($p->name(), '.dat'));
            } elseif (!is_null($sticker_sheet)) {
                foreach ($sticker_sheet->complete_set() as $s) {
                    $part['part']['part_num'] = basename($s->name(), '.dat');
                    $this->addPart($part, $part['part']['part_num'], 16);
                }
            } else {
                $this->messages->add('missing', "<a class=\"underline decoration-dotted hover:decoration-solid\" href=\"{$part['part']['part_url']}\">{$part['part']['part_num']} ({$part['part']['name']})</a>");
            }
        }

        return $this->makePbg();
    }

    protected function addPart(array $part, ?string $ldraw_number = null, ?int $color = null): void
    {
        if (!array_key_exists('LDraw', $part['color']['external_ids']) && is_null($color)) {
            $this->messages->add('errors', 'LDraw color not found for ' . $part['color']['name'] . ', color 16 used instead');
            $color = 16;
        } elseif (array_key_exists('LDraw', $part['color']['external_ids'])) {
            $color = $part['color']['external_ids']['LDraw']['ext_ids'][0];
        }

        $rb_part_num = $part['part']['part_num'];
        $ldraw_part = $ldraw_number ?? $part['part']['external_ids']['LDraw'][0];
        $p = Part::where('filename', "parts/{$ldraw_part}.dat")->doesntHave('unofficial_part')->first();
        if (!is_null($p)) {
            if ($p->category == PartCategory::Moved) {
                $ldraw_part = basename($p->subparts->first()->filename, '.dat');
            } elseif ($p->type_qualifier == PartTypeQualifier::Alias) {
                $ldraw_part = basename($p->subparts->first()->filename, '.dat');
            }
        }
        $quantity = $part['quantity'];

        if (array_key_exists($rb_part_num, $this->parts) && array_key_exists($color, $this->parts[$rb_part_num]['colors'])) {
            $this->parts[$rb_part_num]['colors'][$color] += $quantity;
        } elseif (array_key_exists($rb_part_num, $this->parts)) {
            $this->parts[$rb_part_num]['colors'][$color] = $quantity;
        } else {
            $this->parts[$rb_part_num] = ['ldraw_part' => $ldraw_part, 'colors' => [$color => $quantity]];
        }
    }

    protected function makePbg(): string
    {
        $num = $this->set['set_num'];
        $name = $this->set['name'];
        $result = [
            "[options]",
            "kind=basic",
            "caption=Set {$num} - {$name}",
            "description=Parts in set {$num}",
            "sortDesc=false",
            "sortOn=description",
            "sortCaseInSens=true",
            "<items>"
        ];
        foreach ($this->parts as $part) {
            foreach ($part['colors'] as $color => $quantity) {
                $filename = $part['ldraw_part'];
                $result[] = "{$filename}.dat: [color={$color}][count={$quantity}]";
            }
        }

        return implode("\n", $result);
    }

}
