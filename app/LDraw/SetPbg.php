<?php

namespace App\LDraw;

use App\Models\Part;
use Illuminate\Support\MessageBag;

class SetPbg
{
    public MessageBag $messages;
    public array $parts = [];
    protected ?array $set = null;
    protected Rebrickable $rb;

    public function __construct(?string $set_number = null)
    {
        $this->rb = app(Rebrickable::class);
        $this->messages = new MessageBag();
        if (!is_null($set_number)) {
            $this->set = $this->rb->getSet($set_number);
        }
    }

    public function pbg(?string $set_number = null): string|false
    {

        if ((is_null($set_number) && !is_null($this->set)) ||
            (!is_null($this->set) && $this->set['set_num'] == $set_number)
            && count($this->parts) > 0) {
            return $this->makePbg();
        } elseif (is_null($set_number) && is_null($this->set)) {
            $this->messages = new MessageBag();
            $this->messages->add('errors', 'Set number empty');
            return false;
        }

        $this->parts = [];


        $this->set = $this->rb->getSet($set_number);

        if (is_null($this->set)) {
            $this->messages->add('errors', 'Set Not Found');
            return false;
        }

        $rb_parts = $this->rb->getSetParts($set_number);
        if ($rb_parts->whereNull('ldraw_part_number')->whereNotNull('print_of')->pluck('print_of')->count() > 0) {
            $unpatterned = $this->rb->getParts($rb_parts->whereNull('ldraw_part_number')->whereNotNull('print_of')->pluck('print_of')->all());
        } else {
            $unpatterned = collect([]);
        }

        $rb_parts = $rb_parts->map(function (array $part) use ($unpatterned) {
            if (is_null($part['ldraw_part_number']) && !is_null($part['print_of']) && !is_null($unpatterned->where('rb_part_number', $part['print_of'])->whereNotNull('ldraw_part_number')->first())) {
                $unprinted_part = $unpatterned->where('rb_part_number', $part['print_of'])->whereNotNull('ldraw_part_number')->first();
                $this->messages->add('unpatterned', $part['rb_part_number'] . " ({$unprinted_part['ldraw_part_number']})");
                $part['ldraw_part_number'] = $unprinted_part['ldraw_part_number'];
                $part['rb_part_number'] = $unprinted_part['rb_part_number'];
            }
            return $part;
        });

        foreach ($rb_parts->whereNotNull('ldraw_part_number') as $part) {
            $this->addPart($part);
        }

        foreach ($rb_parts->whereNull('ldraw_part_number') as $part) {
            $p = Part::firstWhere('filename', 'parts/' . $part['rb_part_number'] . '.dat');
            if (is_null($p)) {
                $this->messages->add('missing', "<a class=\"underline decoration-dotted hover:decoration-solid\" href=\"{$part['rb_part_url']}\">{$part['rb_part_number']} ({$part['rb_part_name']})</a>");
            } else {
                $this->addPart($part, basename($p->name(), '.dat'));
            }
        }

        return $this->makePbg();
    }

    protected function addPart(array $rb_part, ?string $ldraw_number = null): void
    {
        if (is_null($rb_part['ldraw_color_number'])) {
            $this->messages->add('errors', 'LDraw color not found for ' . $rb_part['color_name']);
        }

        $rb_part_num = $rb_part['rb_part_number'];
        $ldraw_part = $ldraw_number ?? $rb_part['ldraw_part_number'];
        $color = $rb_part['ldraw_color_number'] ?? 16;
        $quantity = $rb_part['quantity'];

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
