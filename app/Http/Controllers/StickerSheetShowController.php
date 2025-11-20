<?php

namespace App\Http\Controllers;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Services\LDraw\Managers\StickerSheetManager;
use Illuminate\View\View;

class StickerSheetShowController extends Controller
{
    public function __construct(
        protected StickerSheetManager $manager
    )
    {}
    public function __invoke(RebrickablePart $sheet): View
    {
        if ($sheet->rb_part_category_id !== 58) {
            abort(404, 'Not a sticker sheet');
        }
        $flat = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category', PartCategory::Sticker)
            ->filter(fn (Part $part) => !$this->manager->isFormed($part))
            ->sortBy('filename');
        $formed = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category', PartCategory::Sticker)
            ->filter(fn (Part $part) => $this->manager->isFormed($part))
            ->sortBy('filename');
        $shortcuts = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category', PartCategory::StickerShortcut)
            ->sortBy('filename');
        return view('sticker-sheet.show', compact('sheet', 'flat', 'formed', 'shortcuts'));
    }
}
