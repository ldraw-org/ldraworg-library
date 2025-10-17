<?php

namespace App\Http\Controllers\Part;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\LDraw\SupportFiles;
use App\Models\Part\PartRelease;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PartUpdateController extends Controller
{
    public function index(Request $request): Response|View
    {
        if ($request->has('output') && in_array(strtolower($request->query('output')), ['xml', 'tab'])) {
            $output = strtolower($request->query('output'));
            if ($output === 'tab') {
                return response(SupportFiles::ptReleases('tab'))->header('Content-Type', 'text/plain; charset=utf-8');
            }
            return response(SupportFiles::ptReleases('xml'))->header('Content-Type', 'application/xml; charset=utf-8');
        }
        if ($request->has('latest')) {
            $releases = PartRelease::current();
        } else {
            $releases = PartRelease::latest()->get();
        }
        return view('tracker.release.index', ['releases' => $releases , 'latest' => $request->has('latest')]);
    }

    public function view(PartRelease $release): View
    {
        return view('tracker.release.view', ['release' => $release]);
    }

}
