<?php

namespace App\Http\Controllers;

use App\LDraw\SupportFiles;
use App\Models\Part\Part;
use App\Models\Omr\OmrModel;

class SupportFilesController extends Controller
{
    public function webglpart(Part $part) {
        return app(\App\LDraw\LDrawModelMaker::class)->webGl($part);
    }
    
    public function webglmodel(OmrModel $omrmodel) {
        return app(\App\LDraw\LDrawModelMaker::class)->webGl($omrmodel);
    }
    
    public function categories()
    {
        return response(SupportFiles::categoriesText())->header('Content-Type', 'text/plain');
    }

    public function librarycsv()
    {
        return response(SupportFiles::libaryCsv())->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function ptreleases(string $output)
    {
        $output = strtolower($output);
        if ($output === 'tab') {
            return response(SupportFiles::ptReleases('tab'))->header('Content-Type', 'text/plain; charset=utf-8');
        }
        return response(SupportFiles::ptReleases('xml'))->header('Content-Type', 'application/xml; charset=utf-8');
    }

}
