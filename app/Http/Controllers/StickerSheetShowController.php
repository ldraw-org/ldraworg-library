<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Part\Part;
use App\Models\StickerSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StickerSheetShowController extends Controller
{
    public function __invoke(Request $request, StickerSheet $sheet)
    {
        $flat = $sheet->parts
            ->where('category.category', 'Sticker')
            ->filter(fn(Part $part, int $key) => !Str::contains($part->description, '(Formed)'));
        $formed = $sheet->parts
            ->where('category.category', 'Sticker')
            ->filter(fn(Part $part, int $key) => Str::contains($part->description, '(Formed)'));
        $shortcuts = $sheet->parts->where('category.category', '<>', 'Sticker');
        return view('sticker-sheet.show', compact('sheet', 'flat', 'formed', 'shortcuts'));
    }
}
