<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use App\Models\Part;

class PartDownloadController extends Controller
{
    /**
     * Stream download from database.
     *
     * @param  \App\Models\Part  $part
     * @return \Illuminate\Http\Response
     */

    public function __invoke(Part $part) {
        $if_mod_since = new Carbon(request()->header('If-Modified-Since', date('r', 0)));
        $last_change = $part->lastChange();
        if ($part->lastChange() <= $if_mod_since) {
            return response(null, 304)->header('Last-Modified', $last_change->format('r'));
        } else {
            return response()->streamDownload(function() use ($part) { 
                echo $part->get(); 
            }, 
            basename($part->filename), 
            [
                'Content-Type' => $part->isTexmap() ? 'image/png' : 'text/plain',
                'Last-Modified' => $last_change->format('r')
            ]);
        }
    }
}
