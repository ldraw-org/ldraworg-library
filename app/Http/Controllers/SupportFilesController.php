<?php

namespace App\Http\Controllers;

use App\Services\LDraw\LDrawModelMaker;
use App\Services\LDraw\SupportFiles;
use App\Models\Part\Part;
use App\Models\Omr\OmrModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SupportFilesController extends Controller
{
    public function webglpart(Part $part, LDrawModelMaker $maker): JsonResponse
    {
        return response()->json($maker->webGl($part));
    }

    public function webglmodel(OmrModel $omrmodel, LDrawModelMaker $maker): JsonResponse
    {
        return response()->json($maker->webGl($omrmodel));
    }

    public function categories(): Response
    {
        return response(SupportFiles::categoriesText())->header('Content-Type', 'text/plain');
    }

    public function librarycsv(): Response
    {
        if (!Storage::exists('library/library.csv')) {
            SupportFiles::setLibraryCsv();
        }
        return response(Storage::get('library/library.csv'))->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function ptreleases(string $output): Response
    {
        $output = strtolower($output);
        if ($output === 'tab') {
            return response(SupportFiles::ptReleases('tab'))->header('Content-Type', 'text/plain; charset=utf-8');
        }
        return response(SupportFiles::ptReleases('xml'))->header('Content-Type', 'application/xml; charset=utf-8');
    }

}
