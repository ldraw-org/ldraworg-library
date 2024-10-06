<?php

namespace App\Http\Controllers;

class OfficialFileController extends Controller
{
    public function __invoke(string $file)
    {
        dd($file);
        $if_mod_since = new Carbon(request()->header('If-Modified-Since', date('r', 0)));
        $last_change = $part->lastChange();
        if ($part->lastChange() <= $if_mod_since) {
            return response(null, 304)->header('Last-Modified', $last_change->format('r'));
        } else {
            return response()->streamDownload(
                function () use ($part) {
                    echo $part->get();
                },
                basename($part->filename),
                [
                'Content-Type' => $part->isTexmap() ? 'image/png' : 'text/plain',
                'Last-Modified' => $last_change->format('r')
            ]
            );
        }
    }
}
