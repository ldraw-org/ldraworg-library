<?php

namespace App\Http\Controllers;

use App\Models\Part\Part;
use App\Models\StickerSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StickerSheetShowController extends Controller
{
    public function __invoke(Request $request, StickerSheet $sheet)
    {
        $flat = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category.category', 'Sticker')
            ->filter(fn (Part $part, int $key) => !Str::contains($part->description, '(Formed)'))
            ->sortBy('filename');
        $formed = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category.category', 'Sticker')
            ->filter(fn (Part $part, int $key) => Str::contains($part->description, '(Formed)'))
            ->sortBy('filename');
        $shortcuts = $sheet->parts
            ->whereNull('unofficial_part')
            ->where('category.category', '<>', 'Sticker')
            ->sortBy('filename');
        return view('sticker-sheet.show', compact('sheet', 'flat', 'formed', 'shortcuts'));
    }
}
