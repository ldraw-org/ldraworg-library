<?php

namespace App\Http\Controllers;

use App\Services\LDraw\LDrawModelMaker;
use App\Services\LDraw\SupportFiles;
use App\Models\Part\Part;
use App\Models\Omr\OmrModel;
use App\Services\Support\MakeCategoriesTxt;
use App\Services\Support\MakeLibraryCsv;
use App\Services\Support\MakePtReleases;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SupportFilesController extends Controller
{
    public function __construct(
        protected MakeLibraryCsv $libraryCsv,
        protected MakePtReleases $ptReleases,
        protected MakeCategoriesTxt $categoriesTxt,
    ) {}

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
        if (!Storage::exists('library/categories.txt')) {
            $this->categoriesTxt->handle();
        }
        return response(Storage::get('library/library.csv'))->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function libraryCsv(): Response
    {
        if (!Storage::exists('library/library.csv')) {
            $this->libraryCsv->handle();
        }
        return response(Storage::get('library/library.csv'))->header('Content-Type', 'text/csv; charset=utf-8');
    }

    public function ptreleases(string $output): Response
    {
        $output = strtolower($output);
        if (!Storage::exists('library/ptreleases.tsv') || !Storage::exists('library/ptreleases.xml')) {
            $this->ptReleases->handle();
        }
        if ($output === 'tab') {
            return response(Storage::get('library/ptreleases.tsv'))->header('Content-Type', 'text/plain; charset=utf-8');
        }
        return response(Storage::get('library/ptreleases.xml'))->header('Content-Type', 'application/xml; charset=utf-8');
    }

}
