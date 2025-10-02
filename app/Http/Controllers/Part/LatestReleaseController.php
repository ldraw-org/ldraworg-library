<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartReleaseResource;
use App\Models\Part\PartRelease;

class LatestReleaseController extends Controller
{
    public function __invoke()
    {
        return new PartReleaseResource(PartRelease::current());
    }

}
