<?php

namespace App\Services\LDraw;

use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Services\Parser\ImprovedParser;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LDrawModelMaker
{
    public function partMpd(Part $part): string
    {
        if ($part->isTexmap()) {
            return $part->get(true, true);
        }
        $file = [];
        $topModelName = basename($part->filename, '.dat') . '.ldr';
        $preview = $part->preview ?? '16 0 0 0 1 0 0 0 1 0 0 0 1';
        $file[] = "0 FILE {$topModelName}";
        $file[] = "1 {$preview} {$part->meta_name}";
        $file[] = "0 FILE {$part->meta_name}";
        $file[] = $part->get();
        $this->getPartSubparts($part)
          ->each(function (Part $p) use (&$file) {
            if ($p->isTexmap()) {
                $file[] = $p->get(true, true);
            } else {
                $file[] = "0 FILE {$p->meta_name}";
                $file[] = $p->get();
            }
        });
        return implode("\r\n", $file);
    }

    protected function getPartSubparts(Part $part, $includeSelf = false): Collection
    {
        $query = $includeSelf ? $part->descendantsAndSelf() : $part->descendants();

        $query = $part->isUnofficial()
            ? $query->doesntHave('unofficial_part')
            : $query->official();

        $subpartFilenames = $query->distinct()->pluck('filename');
        
        return Part::with('body')
            ->select('id', 'filename', 'type', 'header')
            ->whereIn('filename', $subpartFilenames)
            ->get();
    }
  
    public function modelMpd(string|OmrModel $model): string
    {
        if ($model instanceof OmrModel) {
            $file = ImprovedParser::dosLineEndings(file_get_contents($model->getFirstMediaPath('file')) . "\r\n");
        } else {
            $file = $model;
        }
        $subpartFilenames = (new ParsedPartCollection($file))->subpartFilenames($file) ?? [];
        $allLibraryFilenames = [];
        Part::whereIn('filename', $subpartFilenames)
            ->each(function (Part $part) use (&$allLibraryFilenames) {
                $this->getPartSubparts($part, true)
                    ->each(function (Part $p) use (&$allLibraryFilenames) {
                        $allLibraryFilenames[$p->filename] = true;
                    });
            }); 
        $lines = [];
        Part::with('body')
          ->select('id', 'filename', 'type', 'header')
          ->whereIn('filename', array_keys($allLibraryFilenames))
          ->each(function (Part $p) use (&$lines) {
                if ($p->isTexmap()) {
                    $lines[] = $p->get(true, true);
                } else {
                    $lines[] = "0 FILE {$p->meta_name}";
                    $lines[] = $p->get();
                }
            });
        return $file .= implode("\r\n", $lines) . "\r\n";;
    }


    public function diff(Part $part1, Part $part2): string
    {
        $lines1 = collect(explode("\n", $part1->body->body))
            ->filter(fn($line) => $line !== '' && $line[0] !== '0')
            ->values();
    
        $lines2 = collect(explode("\n", $part2->body->body))
            ->filter(fn($line) => $line !== '' && $line[0] !== '0')
            ->values();
    
        $delcolor   = ['1'=>'36','2'=>'12','3'=>'36','4'=>'36','5'=>'12'];
        $addcolor   = ['1'=>'2','2'=>'10','3'=>'2','4'=>'2','5'=>'10'];
        $matchcolor = ['1'=>'15','2'=>'8','3'=>'15','4'=>'15','5'=>'8'];
    
        $m = $lines1->count();
        $n = $lines2->count();
    
        $prev = array_fill(0, $n + 1, 0);
        $curr = array_fill(0, $n + 1, 0);
    
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $curr[$j+1] = ($lines1[$i] === $lines2[$j])
                    ? $prev[$j] + 1
                    : max($prev[$j+1], $curr[$j]);
            }
            [$prev, $curr] = [$curr, $prev];
        }
    
        $diffLines = collect();
        $backtrack = function($i, $j) use (&$lines1, &$lines2, &$delcolor, &$addcolor, &$matchcolor, &$prev, &$diffLines, &$backtrack) {
            if ($i > 0 && $j > 0 && $lines1[$i-1] === $lines2[$j-1]) {
                $backtrack($i-1, $j-1);
                $diffLines->push(
                    preg_replace_callback('#^([12345]) (\d+)#', fn($m) => $m[1] . ' ' . $matchcolor[$m[1]], $lines1[$i-1])
                );
            } elseif ($j > 0 && ($i === 0 || $prev[$j] >= $prev[$j-1])) {
                $backtrack($i, $j-1);
                $diffLines->push(
                    preg_replace_callback('#^([12345]) (\d+)#', fn($m) => $m[1] . ' ' . $addcolor[$m[1]], $lines2[$j-1])
                );
            } elseif ($i > 0) {
                $backtrack($i-1, $j);
                $diffLines->push(
                    preg_replace_callback('#^([12345]) (\d+)#', fn($m) => $m[1] . ' ' . $delcolor[$m[1]], $lines1[$i-1])
                );
            }
        };
    
        $backtrack($m, $n);
    
        return $diffLines->implode("\n");
    }

    public function webGl(string|OmrModel|Part $model): array
    {
        $webgl = [];
        if ($model instanceof Part) {
            $this->getPartSubparts($model, true)
                ->each(function (Part $p) use (&$webgl) {
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
