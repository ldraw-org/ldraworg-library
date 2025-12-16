<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Services\LDraw\ZipFiles;
use Illuminate\Support\Carbon;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Response;
  
class PartDownloadController extends Controller
{
    public function __construct(
        protected ZipFiles $zipfiles
    )
    {}

    public function __invoke(string $library, string $filename): StreamedResponse|Response
    {
        $is_zip = false;
        if (Str::endsWith($filename, '.zip')) {
            $is_zip = true;
            $filename = Str::replaceLast('.zip', '.dat', $filename);
        }

        $part = Part::when(
            $library === 'official',
            fn (Builder $query) => $query->official(),
            fn (Builder $query) => $query->unofficial()
        )
                    ->when($is_zip, fn (Builder $query) => $query->partsFolderOnly())
                    ->where('filename', $filename)
                    ->firstOrFail();
        if ($is_zip) {
            $contents = $this->zipfiles->partZip($part);
            return response()->streamDownload(
                function () use ($contents) {
                    echo $contents;
                },
                Str::replaceLast('.dat', '.zip', basename($filename)),
                [
                    'Content-Type' => 'application/zip',
                ]
            );
        } else {
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
}
