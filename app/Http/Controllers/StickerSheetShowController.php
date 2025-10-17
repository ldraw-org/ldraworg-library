<?php

namespace App\Http\Controllers;

use App\Enums\PartCategory;
use App\Models\StickerSheet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StickerSheetShowController extends Controller
{
    public function __invoke(StickerSheet $sheet): View
    {
        $flat = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category', PartCategory::Sticker)
            ->where('is_composite', false)
            ->sortBy('filename');
        $formed = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category', PartCategory::Sticker)
            ->where('is_composite', true)
            ->sortBy('filename');
        $shortcuts = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category', '!=', PartCategory::Sticker)
            ->sortBy('filename');
        return view('sticker-sheet.show', compact('sheet', 'flat', 'formed', 'shortcuts'));
    }
}
