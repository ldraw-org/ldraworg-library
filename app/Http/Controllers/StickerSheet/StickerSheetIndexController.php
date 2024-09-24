<?php

namespace App\Http\Controllers\StickerSheet;

use App\Http\Controllers\Controller;
use App\Models\StickerSheet;
use Illuminate\Http\Request;

class StickerSheetIndexController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('sticker-sheet.index');
    }
}
