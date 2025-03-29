<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Part::has('sticker_sheet')
            ->whereDoesntHave('childrenAndSelf',
                fn ($query) => $query->where('category', PartCategory::Sticker)
            )
            ->each(function (Part $p) {
                $p->sticker_sheet_id = null;
                if ($p->category == PartCategory::StickerShortcut) {
                    $word = 1;
                    if (Str::of($p->description)->trim()->words(1,'')->replace(['~', '|', '=', '_'], '') == '') {
                        $word = 2;
                    }
                    $cat = Str::of($p->description)->trim()->words($word,'')->replace(['~', '|', '=', '_', ' '], '')->toString();
                    $cat = PartCategory::tryFrom($cat);
                    if (!is_null($cat)) {
                        $p->category = $cat;
                        if (!$p->isUnofficial()) {
                            $p->has_minor_edit = true;
                        }
                    }
                    $p->generateHeader();
                }
                $p->save();
            });
     }
}
