<?php

namespace App\Http\Controllers\StickerSheet;

use App\Http\Controllers\Controller;
use App\Models\StickerSheet;
use Illuminate\Http\Request;

class StickerSheetShowController extends Controller
{
    public function __invoke(Request $request, StickerSheet $sheet)
    {
        return view('sticker-sheet.show', compact('sheet'));
    }
}
