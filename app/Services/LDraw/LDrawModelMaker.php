<?php

namespace App\Services\LDraw;

use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Services\Parser\ImprovedParser;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LDrawModelMaker
{
    public function partMpd(Part $part): string
    {
        if ($part->isTexmap()) {
            return $part->get(true, true);
        }
        $topModelName = basename($part->filename, '.dat') . '.ldr';
        $preview = $part->preview ?? '16 0 0 0 1 0 0 0 1 0 0 0 1';
        $file = "0 FILE {$topModelName}\r\n1 {$preview} {$part->meta_name}\r\n0 FILE {$part->meta_name}\r\n{$part->get()}\r\n";
        if ($part->isUnofficial()) {
            $sparts = $part->descendants()->doesntHave('unofficial_part')->get()->unique('filename');
        } else {
            $sparts = $part->descendants->official()->unique('filename');
        }
        $sparts->load('body');
        $sparts->each(function (Part $p) use (&$file) {
            if ($p->isTexmap()) {
                $file .= $p->get(true, true);
            } else {
                $file .= "0 FILE {$p->meta_name}\r\n{$p->get()}\r\n";
            }
        });
        return $file;
    }

    public function modelMpd(string|OmrModel $model): string
    {
        if ($model instanceof OmrModel) {
            $file = ImprovedParser::dosLineEndings(file_get_contents($model->getFirstMediaPath('file')) . "\r\n");
        } else {
            $file = $model;
        }
        $subparts = collect((new ParsedPartCollection($file))->subparts($file));
        $subs = $subparts
            ->map( function (string $subpart) {
                $subpart = Str::of($subpart)->replace('\\', '/');
                if (pathinfo($subpart, PATHINFO_EXTENSION) == '.png') {
                    return $subpart->prepend('parts/textures/')->toString();
                }
                return $subpart->prepend('parts/')->toString();
            })
            ->merge(
                $subparts
                ->map( function (string $subpart) {
                    $subpart = Str::of($subpart)->replace('\\', '/');
                    if (pathinfo($subpart, PATHINFO_EXTENSION) == '.png') {
                        return $subpart->prepend('p/textures/')->toString();
                    }
                    return $subpart->prepend('p/')->toString();
                })
            )
            ->all();
        $parts = new Collection();
        Part::with('descendantsAndSelf')
            ->doesntHave('unofficial_part')
            ->whereIn('filename', $subs)
            ->each(function (Part $p) use (&$parts) {
                $parts = $parts->merge($p->descendantsAndSelf()->doesntHave('unofficial_part')->get()->unique('filename')->all())->unique('filename');
            });
        $parts
            ->each(function (Part $p) use (&$file) {
                if ($p->isTexmap()) {
                    $file .= $p->get(true, true);
                } else {
                    $file .= "0 FILE {$p->meta_name}\r\n{$p->get()}\r\n";
                }
            });
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

    public function webGl(string|OmrModel|Part $model): array
    {
        $webgl = [];
        if ($model instanceof Part) {
            if ($model->isUnofficial()) {
                $sparts = $model->descendantsAndSelf()->doesntHave('unofficial_part')->get()->unique('filename');
            } else {
                $sparts = $model->descendantsAndSelf->official()->unique('filename');
            }
            $sparts->load('body');
            $sparts->each(function (Part $p) use (&$webgl) {
                $text = base64_encode($p->get());
                $name = Str::chopStart($p->filename, ['parts/', 'p/']);
                if ($p->isTexmap()) {
                    $name = Str::chopStart($name, 'textures/');
                    $webgl[$name] = "data:img/png;base64,{$text}";
                } else {
                    $webgl[$name] = "data:text/plain;base64,{$text}";
                }
            });
        } elseif ($model instanceof OmrModel) {
            $webgl[$model->filename()] = 'data:text/plain;base64,' . base64_encode($this->modelMpd($model));
        } else {
            $isMpd = preg_match('/^0\h+FILE\h+((?:.*?)(?:\.ldr|\.dat|\.mpd))/i', $model, $match);
            if ($isMpd) {
                $model = "0 FILE model.ldr\r\n1 16 0 0 0 1 0 0 0 1 0 0 0 1 {$match[1]}\r\n$model";
            } else {
                $model = "0 FILE model.ldr\r\n$model";
            }
            $webgl['model.ldr'] = 'data:text/plain;base64,' . base64_encode($this->modelMpd($model));
        }
        $webgl['ldconfig.ldr'] = 'data:text/plain;base64,' . base64_encode(Storage::disk('library')->get('official/LDConfig.ldr'));
        return $webgl;
    }

}
