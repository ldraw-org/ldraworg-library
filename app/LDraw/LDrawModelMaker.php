<?php

namespace App\LDraw;

use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class LDrawModelMaker
{
    public function partMpd(Part $part, string $matrix = '1 0 0 0 1 0 0 0 1'): string
    {
        if ($part->isTexmap()) {
            return $part->get(true, true);
        }

        $topModelName = pathinfo($part->filename, PATHINFO_FILENAME) . '.ldr';
        $file = "0 FILE {$topModelName}\r\n1 16 0 0 0 {$matrix} {$part->name()}\r\n0 FILE {$part->name()}\r\n{$part->get()}\r\n";
        if ($part->isUnofficial()) {
            $sparts = $part->descendants->whereNull('unofficial_part');
        } else {
            $sparts = $part->descendants->whereNotNull('part_release_id');
        }
        foreach ($sparts as $s) {
            /** @var Part $s */
            if ($s->isTexmap()) {
                $file .= $s->get(true, true);
            } else {
                $file .= "0 FILE {$s->name()}\r\n{$s->get()}\r\n";
            }
        }
        return $file;
    }

    public function modelMpd(OmrModel $model): string
    {
        $file = app(\App\LDraw\Parse\Parser::class)->unix2dos(Storage::disk('library')->get("omr/{$model->filename()}") . "\r\n");
        $parts = app(\App\LDraw\Parse\Parser::class)->getSubparts($file);
        $subs = [];
        foreach ($parts['subparts'] ?? [] as $s) {
            $s = str_replace('\\', '/', $s);
            $subs[] = "parts/{$s}";
            $subs[] = "p/{$s}";
        }
        foreach ($parts['textures'] ?? [] as $s) {
            $s = str_replace('\\', '/', $s);
            $subs[] = "parts/textures/{$s}";
            $subs[] = "p/textures/{$s}";
        }
        $oparts = new Collection();
        foreach (Part::official()->whereIn('filename', $subs)->get() as $part) {
            $oparts = $oparts->merge($part->descendantsAndSelf()->official()->get());
        }
        $oparts = $oparts->unique();
        foreach ($oparts as $s) {
            /** @var Part $s */
            if ($s->isTexmap()) {
                $file .= $s->get(true, true);
            } else {
                $file .= "0 FILE {$s->name()}\r\n{$s->get()}\r\n";
            }
        }
        return $file;
    }

    public function diff(Part $part1, Part $part2): string
    {
        $lines = collect(explode("\n", $part1->body->body))->filter(function (string $value) {
            return !empty($value) && $value[0] != "0";
        });
        $lines2 = collect(explode("\n", $part2->body->body))->filter(function (string $value) {
            return !empty($value) && $value[0] != "0";
        });
        $pattern = '#^([12345]) (\d+)#';
        $delcolor   = ['1' => '36', '2' => '12', '3' => '36', '4' => '36', '5' => '12'];
        $addcolor   = ['1' =>  '2', '2' => '10', '3' =>  '2', '4' =>  '2', '5' => '10'];
        $matchcolor = ['1' => '15', '2' =>  '8', '3' => '15', '4' => '15', '5' =>  '8'];
        $same = $lines->intersect($lines2)->transform(function (string $item) use ($pattern, $matchcolor) {
            return preg_replace($pattern, '$1 '. $matchcolor[$item[0]], $item);
        });
        $added = $lines2->diff($lines)->transform(function (string $item) use ($pattern, $addcolor) {
            return preg_replace($pattern, '$1 '. $addcolor[$item[0]], $item);
        });
        $removed = $lines->diff($lines2)->transform(function (string $item) use ($pattern, $delcolor) {
            return preg_replace($pattern, '$1 '. $delcolor[$item[0]], $item);
        });
        return implode("\n", array_merge($same->toArray(), $added->toArray(), $removed->toArray()));
    }

}
