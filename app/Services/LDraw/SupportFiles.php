<?php

namespace App\Services\LDraw;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\Storage;

class SupportFiles
{
    public static function categoriesText()
    {
        return implode("\n", array_column(PartCategory::cases(), 'value'));
    }

    public static function setLibraryCsv(): void
    {
        $csv = "part_number,part_description,part_url,image_url,image_last_modified\n";
        $csv .= Part::select('id', 'filename', 'description', 'part_release_id')
            ->with('media')
            ->doesntHave('official_part')
            ->partsFolderOnly()
            ->where('description', 'NOT LIKE', '=%')
            ->where('description', 'NOT LIKE', '|%')
            ->where('description', 'NOT LIKE', '~%')
            ->where('description', 'NOT LIKE', '\_%')
            ->whereNotIn('category', [PartCategory::Moved, PartCategory::Obsolete])
            ->get()
            ->map(function (Part $part) {
                $media = $part->getFirstMedia('image');
                return basename($part->filename)
                    . ',"' . str_replace('"', '""', $part->description) . '"'
                    . ',' . route('part.download', ['library' => is_null($part->part_release_id) ? 'unofficial' : 'official', 'filename' => $part->filename])
                    . ',' . version('media/ldraw/' . $part->libFolder() . '/' . substr($part->filename, 0, -3) . 'png')
                    . ',' . $media->created_at->format('Y-m-d');
            })
            ->implode("\n");
        Storage::disk('library')->put('library.csv', $csv);
    }

    public static function ptReleases(string $output = "xml"): string
    {
        $releases = PartRelease::where('short', '!=', 'original')->oldest()->get();
        if ($output === 'tab') {
            $ptreleases = '';
        } else {
            $ptreleases = '<releases>';
        }
        foreach ($releases as $release) {
            $ptreleases .=
                self::ptReleaseEntry(
                    'UPDATE',
                    'ARJ',
                    $release->name,
                    date_format($release->created_at, 'Y-m-d'),
                    "updates/lcad{$release->short}.exe",
                    $output
                );
            $ptreleases .=
                self::ptReleaseEntry(
                    'UPDATE',
                    'ZIP',
                    $release->name,
                    date_format($release->created_at, 'Y-m-d'),
                    "updates/lcad{$release->short}.zip",
                    $output
                );
        }
        $current = PartRelease::current();
        $ptreleases .=
            self::ptReleaseEntry(
                'COMPLETE',
                'ARJ',
                $current->name,
                date_format($current->created_at, 'Y-m-d'),
                "updates/complete.exe",
                $output
            );
        $ptreleases .=
            self::ptReleaseEntry(
                'COMPLETE',
                'ZIP',
                $current->name,
                date_format($current->created_at, 'Y-m-d'),
                "updates/complete.zip",
                $output
            );
        $ptreleases .=
            self::ptReleaseEntry(
                'BASE',
                'ARJ',
                '0.27',
                date('Y-m-d', Storage::disk('library')->lastModified("updates/ldraw027.exe")),
                "updates/ldraw027.exe",
                $output
            );
        $ptreleases .=
            self::ptReleaseEntry(
                'BASE',
                'ZIP',
                '0.27',
                date('Y-m-d', Storage::disk('library')->lastModified("updates/ldraw027.zip")),
                "updates/ldraw027.zip",
                $output
            );
        if ($output !== 'tab') {
            $ptreleases .= '</releases>';
        }
        return $ptreleases;
    }

    protected static function ptReleaseEntry(string $type, string $format, string $name, string $date, string $file, string $output = "xml"): string
    {
        if (Storage::disk('library')->exists($file)) {
            $url = Storage::disk('library')->url($file);
            $size = Storage::disk('library')->size($file);
            $checksum = Storage::disk('library')->checksum($file);
            if ($output === 'tab') {
                return "{$type}\t{$name}\t{$date}\t{$format}\t{$url}\t{$size}\t{$checksum}\n";
            }

            return "<distribution><release_type>{$type}</release_type><release_id>{$name}</release_id>" .
                "<release_date>{$date}</release_date>" .
                "<file_format>{$format}</file_format>" .
                "<url>{$url}</url>" .
                "<size>{$size}</size>" .
                "<md5_fingerprint>{$checksum}</md5_fingerprint></distribution>\n";
        }
        return '';
    }
}
