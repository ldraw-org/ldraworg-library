<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(PartManager $manager): void
    {
        $partIds = collect();
        PartKeyword::whereLike('keyword', '{"id":%')
            ->each(function (PartKeyword $keyword) use (&$partIds) {
                preg_match('~"keyword":"(.*?)"~', $keyword->keyword, $match);
                $newKeyword = PartKeyword::updateOrCreate(['keyword' => $match[1]]);
                $oldId = $keyword->id;
                $newId = $newKeyword->id;
                $partIds = $partIds->merge($keyword->parts->pluck('id'));
                $keyword->parts->each(function (Part $part) use ($oldId, $newId) {
                    $ids = $part->keywords
                        ->pluck('id')
                        ->filter(fn ($id) => $id != $oldId)
                        ->push($newId)
                        ->unique()
                        ->values()
                        ->all();
                    $part->keywords()->sync($ids);
                });
            });
        $ids = $partIds->flatten()->unique()->values()->all();
        $updateZipPath = Storage::disk('library')->path('updates/lcad2512.zip');
        $completeZipPath = Storage::disk('library')->path('updates/complete.zip');
        $updateZip = new \ZipArchive();
        $completeZip = new \ZipArchive();
        Part::whereIn('id', $ids)
            ->each(function (Part $part) use ($updateZipPath, $completeZipPath, $updateZip, $completeZip) {
                $part->has_minor_edit = true;
                $part->generateHeader();
                app(\App\Services\LDraw\Managers\Part\PartManager::class)->checkPart($part);
                $updateZip->open($updateZipPath);
                $completeZip->open($completeZipPath);
                $filename = "ldraw/{$part->filename}";
                $file = $part->get();
                $updateZip->deleteName($filename);
                $completeZip->deleteName($filename);
                $timestamp = $part->lastChange()->getTimestamp();
                
                $updateZip->addFromString($filename, $file);
                $updateZip->setMtimeName($filename, $timestamp);
                $completeZip->addFromString($filename, $file);
                $completeZip->setMtimeName($filename, $timestamp);

                $updateZip->close();
                $completeZip->close();
            });
    }
}
