<?php

namespace App\Http\Controllers\Part;

use App\Services\Support\Enums\ReleaseOutput;
use App\Services\Support\MakePtReleases;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Part\PartRelease;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PartUpdateController extends Controller
{
    public function __construct(
        protected MakePtReleases $makePtReleases
    ) {}

    public function index(Request $request): Response|RedirectResponse|View
    {
        if ($request->has('output')) {
            return redirect()->route('ptreleases', ['output' => $request->query('output')]);
        }
        if ($request->has('latest')) {
            $releases = PartRelease::current();
        } else {
            $releases = PartRelease::with('media')->latest()->get();
        }
        return view('tracker.release.index', ['releases' => $releases , 'latest' => $request->has('latest')]);
    }

    public function view(PartRelease $release): View
    {
        return view('tracker.release.view', ['release' => $release]);
    }

}
