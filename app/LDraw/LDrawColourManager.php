<?php

namespace App\LDraw;

use App\LDraw\Parse\Parser;
use App\Models\LdrawColour;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LDrawColourManager
{
    public function __construct(
        public Parser $parser,
    ) {
    }

    public function importColours(): void
    {
        $ldconfig = Storage::disk('library')->get('official/LDConfig.ldr');
        $colors = $this->parser->getColours($this->parser->unixLineEndings($ldconfig)) ?? [];
        foreach ($colors as $color) {
            LdrawColour::updateOrCreate(['code' => $color['code']], $color);
        }
        Cache::set('ldraw_colour_codes', LdrawColour::pluck('code')->all());
    }
}
