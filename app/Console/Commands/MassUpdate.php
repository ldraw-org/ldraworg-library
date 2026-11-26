<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Part\TexmapOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class MassUpdate extends Command
{
    protected $signature = 'lib:mass-update';

    protected $description = 'A command to do focused mass updates';

    /**
     * Losslessly optimize every texmap body, flagging changed official parts as minor edits.
     */
    public function handle(TexmapOptimizer $texmapOptimizer): void
    {
        $optimized = 0;
        $unofficialChanged = 0;
        $bytesSaved = 0;

        Part::with('body')
            ->whereIn('type', PartType::imageFormat())
            ->lazyById(20)
            ->each(function (Part $part) use ($texmapOptimizer, &$optimized, &$unofficialChanged, &$bytesSaved): void {
                $png = base64_decode($part->body->body);
                $optimizedPng = $texmapOptimizer->optimize($png);
                if ($optimizedPng === $png) {
                    return;
                }

                DB::transaction(function () use ($part, $optimizedPng): void {
                    $part->setBody(base64_encode($optimizedPng));
                    if ($part->isOfficial()) {
                        $part->has_minor_edit = true;
                        $part->save();
                    }
                });

                $optimized++;
                $unofficialChanged += $part->isUnofficial() ? 1 : 0;
                $bytesSaved += strlen($png) - strlen($optimizedPng);
            });

        $this->info("Optimized {$optimized} texmaps, saving " . Number::fileSize($bytesSaved, 1));

        if ($unofficialChanged > 0) {
            $this->call('lib:refresh-zip');
        }
    }
}
