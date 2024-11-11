<?php

namespace App\LDraw;

use App\Models\LdrawColour;
use Illuminate\Support\Facades\Storage;

class LDrawColourManager
{
    public static function importColours(): void
    {
        $ldconfig = Storage::disk('library')->get('official/LDConfig.ldr');
        $ldconfig = preg_replace("#\R#", "\n", $ldconfig);
        $result = preg_match_all(config('ldraw.patterns.colour'), $ldconfig, $colours, PREG_SET_ORDER);
        if ($result) {
            foreach($colours as $color) {
                $color = array_filter($color, fn ($k) => is_string($k), ARRAY_FILTER_USE_KEY);
                $color = array_map(function($val) { return $val == '' ? null : $val; }, $color);
                $color = array_merge(['alpha' => null, 'luminance' => null, 'material' => null], $color);
                $material = $color['material'];
                unset($color['material']);
                if (!is_null($material)) {
                    $material_result = preg_match(config('ldraw.patterns.colour_material'), $material, $values);
                    if ($material_result) {
                        $values = array_filter($values, fn ($k) => is_string($k), ARRAY_FILTER_USE_KEY);
                        $values = array_map(function($val) { return $val == '' ? null : $val; }, $values);
                        $values = array_merge(['alpha' => null, 'luminance' => null, 'vfraction' => null, 'size' => null, 'maxsize' => null, 'minsize' => null], $values);
                        foreach($values as $index => $value) {
                            if ($index == 'type') {
                                $color[mb_strtolower($value)] = true;
                            } else {
                                $color["material_{$index}"] = $value;
                            }
                        }
                    } else {
                        $material = mb_strtolower($material);
                        $color[$material] = true;
                    }
                }
                LdrawColour::updateOrCreate(['code' => $color['code']], $color);
            }
        }        
    }
}