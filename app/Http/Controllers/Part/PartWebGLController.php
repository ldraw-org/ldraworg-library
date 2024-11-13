<?php

namespace App\Http\Controllers\Part;

use App\Models\Part\Part;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class PartWebGLController extends Controller
{
    public function __invoke(Part $part)
    {
        $parts = $part->descendantsAndSelf();
        if ($part->isUnofficial()) {
            $parts = $parts->doesntHave('unofficial_part');
        } else {
            $parts = $parts->whereNotNull('part_release_id');
        }
        $webgl = [];
        $parts->each(function (Part $p) use (&$webgl) {
            if ($p->isTexmap()) {
                $pn = str_replace(["parts/textures/","p/textures/"], '', $p->filename);
                $webgl[$pn] = 'data:image/png;base64,' .  base64_encode($p->get());
            } else {
                $pn = str_replace(["parts/","p/"], '', $p->filename);
                $webgl[$pn] = 'data:text/plain;base64,' .  base64_encode($p->get());
            }
        });
        $webgl['ldconfig.ldr'] = 'data:text/plain;base64,' . base64_encode(Storage::disk('library')->get('official/LDConfig.ldr'));
        return $webgl;
    }
}
