<?php

namespace App\Http\Controllers\Omr;

use App\Models\Omr\OmrModel;
use App\Http\Controllers\Controller;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class OmrModelWebGLController extends Controller
{
    public function __invoke(OmrModel $model)
    {
        $file = app(\App\LDraw\Parse\Parser::class)->unix2dos(Storage::disk('library')->get("omr/{$model->filename()}") . "\r\n");
        $parts = app(\App\LDraw\Parse\Parser::class)->getSubparts($file);
        $subs = [];
        foreach ($parts['subparts'] ?? [] as $s) {
            $s = str_replace('\\', '/', $s);
            $subs[] = "parts/{$s}";
            $subs[] = "p/{$s}";
        }
        foreach ($parts['textures'] ?? [] as $s) {
            $s = str_replace('\\', '/', $s);
            $subs[] = "parts/textures/{$s}";
            $subs[] = "p/textures/{$s}";
        }
        $oparts = new Collection();
        foreach (Part::official()->whereIn('filename', $subs)->get() as $part) {
            /** var Part $p */
            $oparts = $oparts->merge($part->descendantsAndSelf()->official()->get());
        }
        $oparts = $oparts->unique();
        $webgl = [$model->filename() => 'data:text/plain;base64,' .  base64_encode($file)];
        foreach ($oparts as $p) {
            /** @var Part $p */
            if ($p->isTexmap()) {
                $pn = str_replace(["parts/textures/","p/textures/"], '', $p->filename);
                $webgl[$pn] = 'data:image/png;base64,' .  base64_encode($p->get());
            } else {
                $pn = str_replace(["parts/","p/"], '', $p->filename);
                $webgl[$pn] = 'data:text/plain;base64,' .  base64_encode($p->get());
            }
        };
        return $webgl;
    }
}
