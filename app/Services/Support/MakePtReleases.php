<?php

namespace App\Services\Support;

use App\Models\Part\PartRelease;
use App\Services\Support\Enums\ReleaseFormat;
use App\Services\Support\Enums\ReleaseOutput;
use App\Services\Support\Enums\ReleaseType;
use Illuminate\Support\Facades\Storage;

class MakePtReleases
{
    private const BASE_VERSION = '0.27';

    public function handle(): void
    {
        Storage::put('library/ptreleases.tsv', $this->ptReleases(ReleaseOutput::Tab));
        Storage::put('library/ptreleases.xml', $this->ptReleases());
    }

    public function ptReleases(ReleaseOutput $output = ReleaseOutput::Xml): string
    {
        $entries = collect($this->updateEntries())
            ->merge($this->completeEntries())
            ->merge($this->baseEntries())
            ->map(fn (ReleaseEntry $e) => $this->renderEntry($e, $output))
            ->implode('');

        return match ($output) {
            ReleaseOutput::Xml => "<releases>{$entries}</releases>",
            ReleaseOutput::Tab => $entries,
        };
    }

    private function updateEntries(): array
    {
        return PartRelease::where('short', '!=', 'original')
            ->where('enabled', true)
            ->oldest()
            ->get()
            ->flatMap(fn (PartRelease $release) => [
                new ReleaseEntry(
                    ReleaseType::Update,
                    ReleaseFormat::Arj,
                    $release->name,
                    $release->created_at->toDateString(),
                    "updates/lcad{$release->short}.exe",
                ),
                new ReleaseEntry(
                    ReleaseType::Update,
                    ReleaseFormat::Zip,
                    $release->name,
                    $release->created_at->toDateString(),
                    "updates/lcad{$release->short}.zip",
                ),
            ])
            ->all();
    }

    private function completeEntries(): array
    {
        $current = PartRelease::current();
        $date    = $current->created_at->toDateString();

        return array_map(
            fn (ReleaseFormat $format) => new ReleaseEntry(
                ReleaseType::Complete,
                $format,
                $current->name,
                $date,
                $format->completeFile(),
            ),
            ReleaseFormat::cases(),
        );
    }

    private function baseEntries(): array
    {
        return array_map(
            fn (ReleaseFormat $format) => new ReleaseEntry(
                ReleaseType::Base,
                $format,
                self::BASE_VERSION,
                date('Y-m-d', Storage::lastModified('library/' . $format->baseFile())),
                $format->baseFile(),
            ),
            ReleaseFormat::cases(),
        );
    }

    private function renderEntry(ReleaseEntry $entry, ReleaseOutput $output): string
    {
        $path = "library/{$entry->file}";

        if (! Storage::exists($path)) {
            return '';
        }

        $url      = Storage::disk('library')->url($entry->file);
        $size     = Storage::size($path);
        $checksum = Storage::checksum($path);

        return match ($output) {
            ReleaseOutput::Tab => implode("\t", [
                    $entry->type->value,
                    $entry->name,
                    $entry->date,
                    $entry->format->value,
                    $url,
                    $size,
                    $checksum,
                ]) . "\n",

            ReleaseOutput::Xml => implode('', [
                '<distribution>',
                "<release_type>{$entry->type->value}</release_type>",
                "<release_id>{$entry->name}</release_id>",
                "<release_date>{$entry->date}</release_date>",
                "<file_format>{$entry->format->value}</file_format>",
                "<url>{$url}</url>",
                "<size>{$size}</size>",
                "<md5_fingerprint>{$checksum}</md5_fingerprint>",
                "</distribution>\n",
            ]),
        };
    }
}
