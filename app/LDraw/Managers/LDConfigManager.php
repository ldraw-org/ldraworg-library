<?php

namespace App\LDraw\Managers;

use App\LDraw\Parse\Parser;
use App\Models\Avatar;
use App\Models\LdrawColour;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LDConfigManager
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
            $c = LdrawColour::where('name', $color['name'])->where('code', '!=', $color['code'])->first();
            if (!is_null($c)) {
                $c->delete();
            }
            LdrawColour::updateOrCreate(['code' => $color['code']], $color);
        }
        Cache::set('ldraw_colour_codes', LdrawColour::pluck('code')->all());
    }
    
    public function importAvatars(): void
    {
        $ldconfig = Storage::disk('library')->get('official/LDConfig.ldr');
        $avatars = $this->parser->getAvatars($this->parser->unixLineEndings($ldconfig)) ?? [];
        Avatar::upsert($avatars, uniqueBy: 'category', update: ['part', 'matrix', 'description']);
        Cache::set('avatar_parts', Arr::mapWithKeys($avatars, fn (array $a, int $key) => [$a['category'] => $a['part']]));
    }
}
