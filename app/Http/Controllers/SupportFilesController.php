<?php

namespace App\Http\Controllers;

use App\LDraw\LDrawModelMaker;
use App\LDraw\SupportFiles;
use App\Models\Part\Part;
use App\Models\Omr\OmrModel;
use Illuminate\Support\Facades\Storage;

class SupportFilesController extends Controller
{
    public function webglpart(Part $part)
    {
        return app(LDrawModelMaker::class)->webGl($part);
    }

    public function webglmodel(OmrModel $omrmodel)
    {
        return app(LDrawModelMaker::class)->webGl($omrmodel);
    }

    public function categories()
    {
        return response(SupportFiles::categoriesText())->header('Content-Type', 'text/plain');
    }

    public function librarycsv()
    {
        if (!Storage::disk('library')->exists('library.csv')) {
            SupportFiles::setLibraryCsv();
        }
        return response(Storage::disk('library')->get('library.csv'))->header('Content-Type', 'text/plain; charset=utf-8');
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
