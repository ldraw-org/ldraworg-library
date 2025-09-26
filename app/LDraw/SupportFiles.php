<?php

namespace App\LDraw;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SupportFiles
{
    public static function categoriesText()
    {
        return implode("\n", array_column(PartCategory::cases(), 'value'));
    }

    public static function libaryCsv(): string
    {
        $csv = "part_number,part_description,part_url,image_url,image_last_modified\n";
        foreach (Part::query()->partsFolderOnly()->lazy() as $part) {
            if (in_array($part->description[0], ['~','_','|','='])) {
                continue;
            }
            $image = "{$part->libFolder()}/" . substr($part->filename, 0, -4) . '.png';
            $vals = [
                basename($part->filename),
                '"' . str_replace('"', '""', $part->description) . '"',
                route('part.download', ['library' => $part->libFolder(), 'filename' => $part->filename]),
                $part->getFirstMediaUrl('images'),
                Carbon::createFromTimestamp(Storage::disk('images')->lastModified("library/{$image}"))->format('Y-m-d')
            ];
            $csv .= implode(',', $vals) . "\n";
        }
        return $csv;
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
