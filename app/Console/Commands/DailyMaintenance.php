<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickable;
use App\LDraw\PartManager;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DailyMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:daily-maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Daily Maintenance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reloading all subparts');
        Part::lazy()->each(fn (Part $p) => app(PartManager::class)->loadSubparts($p));

        $this->info('Recounting all votes');
        Part::unofficial()->lazy()->each(fn (Part $p) => $p->updatePartStatus());

        $this->info('Rechecking all unofficial parts');
        $this->call('lib:check', ['--unofficial-only' => true]);

        $this->info('Removing orphan keywords');
        PartKeyword::doesntHave('parts')->delete();

        $this->info('Removing orphan images');
        $images = Storage::disk('images')->allFiles('library/unofficial');
        $files = collect($images)
            ->map(function (string $file): string {
                $file = str_replace('_thumb.png', '.png', $file);
                if (strpos($file, 'textures/') !== false) {
                    return str_replace('library/unofficial/', '', $file);
                } else {
                    return str_replace(['library/unofficial/', '.png'], ['', '.dat'], $file);
                }
            })
            ->unique()
            ->all();
        $in_use_files = Part::unofficial()
            ->whereIn('filename', $files)
            ->pluck('filename')
            ->map(
                fn (string $filename): string =>
                    strpos($filename, 'textures/') !== false ? "library/unofficial/{$filename}" : str_replace('.dat', '.png', "library/unofficial/{$filename}")
            );
        foreach ($images as $image) {
            if (!$in_use_files->contains($image) && !$in_use_files->contains(str_replace('_thumb.png', '.png', $image))) {
                Storage::disk('images')->delete($image);
            }
        }

        $this->info('Refreshing Rebrickable data');
        Part::unofficial()
            ->where(fn ($query) =>
                $query
                    ->orwhereBetween('created_at', [now(), now()->subDay()])
                    ->orWhereBetween('created_at', [now()->subWeek()->subDay(), now()->subWeek()])
                    ->orWhereBetween('created_at', [now()->subMonth()->subDay(), now()->subMonth()])
            )
            ->each(fn (Part $part) => UpdateRebrickable::dispatch($part));

        $this->info('Queueing missing images');
        $this->call('lib:render-parts', ['--unofficial-only' => true, '--missing' => true]);
        $this->call('lib:render-models', ['--missing' => true]);

        $this->info('Reloading colors for LDConfig');
        $this->call('lib:update-colours');

        $this->info('Regenerate unofficial zip');
        $this->call('lib:refresh-zip');

        $this->info('Pruning failed jobs');
        $this->call('queue:prune-failed');
    }
}
