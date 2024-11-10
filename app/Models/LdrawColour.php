<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LdrawColour extends Model
{
    protected $guarded = [
        'id',
        'created_at'
    ];
    
    protected function casts(): array {
        return [
            'chrome' => 'boolean',
            'pearlescent' => 'boolean',
            'rubber' => 'boolean',
            'matte_metallic' => 'boolean',
            'metal' => 'boolean',
            'glitter' => 'boolean',
            'speckle' => 'boolean',
        ];
    }

/*
    public function category(): BelongsTo
    {
        return $this->belongsTo(LdrawColourCategory::class, 'ldraw_colour_category_id', 'id');
    }

    public function ldconfigs(): BelongsToMany
    {
        return $this->belongsToMany(Ldconfig::class, 'ldconfigs_ldraw_colours', 'ldraw_colour_id', 'ldconfig_id');
    }
*/
    public function labelTextColor(): string
    {
        $int = hexdec($this->value);
        $rgb = array("red" => ((0xFF & ($int >> 0x10)) / 255.0), "green" => ((0xFF & ($int >> 0x8)) / 255.0), "blue" => ((0xFF & $int) / 255.0));
        foreach ($rgb as $tcolor => $value) {
            if ($value <= 0.03928) {
              $rgb[$tcolor] = $value / 12.92;
            }
            else {
              $rgb[$tcolor] = (($value + 0.055) / 1.055) ** 2.4;
            }
        }
        $L = 0.2126 * $rgb['red'] + 0.7152 * $rgb['green'] + 0.0722 * $rgb['blue'];
        if ($L > 0.179) {
            return 'text-gray-950';
        }
        
        return 'text-gray-50';
    }

    public function toString(): string {
        $colour = "0 !COLOUR {$this->name} CODE {$this->code} VALUE {$this->value} EDGE {$this->edge}";
        if (!is_null($this->alpha)) {
            $colour .= " APLHA {$this->alpha}";
        }
        if (!is_null($this->luminance)) {
            $colour .= " LUMINANCE {$this->luminance}";
        }
        if ($this->chrome === true) {
            $colour .= " CHROME";
        }
        if ($this->pearlescent === true) {
            $colour .= " PEARLESCENT";
        }
        if ($this->rubber === true) {
            $colour .= " RUBBER";
        }
        if ($this->matte_metallic === true) {
            $colour .= " MATTE_METALLIC";
        }
        if ($this->metal === true) {
            $colour .= " METAL";
        }
        if (!$this->glitter || !$this->speckle) {
            $colour .= " MATERIAL";
            if ($this->glitter == true) {
                $colour .= " GLITTER";
            }
            if ($this->speckle == true) {
                $colour .= " SPECKLE";
            }
            if (!is_null($this->material_value)) {
                $colour .= " VALUE {$this->material_value}";
            }
            if (!is_null($this->material_alpha)) {
                $colour .= " APLHA {$this->material_alpha}";
            }
            if (!is_null($this->material_luminance)) {
                $colour .= " LUMINANCE {$this->material_luminance}";
            }
            if (!is_null($this->material_fraction)) {
                $colour .= " FRACTION {$this->material_fraction}";
            }
            if (!is_null($this->material_vfraction)) {
                $colour .= " VFRACTION {$this->material_vfraction}";
            }
            if (!is_null($this->material_size)) {
                $colour .= " SIZE {$this->material_size}";
            }
            if (!is_null($this->material_minsize)) {
                $colour .= " MINSIZE {$this->material_minsize}";
            }
            if (!is_null($this->material_maxsize)) {
                $colour .= " MAXSIZE {$this->material_maxsize}";
            }
        }
        
        return $colour;
    }
}
