<?php

namespace App\Console\Commands;

use App\Jobs\CheckPart;
use App\Jobs\UpdateImage;
use App\LDraw\PartManager;
use App\Models\Omr\OmrModel;
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
        Part::lazy()->each(fn (Part $p) => app(PartManager::class)->loadSubpartsFromBody($p));

        $this->info('Recounting all votes');
        Part::unofficial()->lazy()->each(fn (Part $p) => $p->updateVoteSort());

        $this->info('Rechecking all unofficial parts');
        Part::unofficial()->lazy()->each(fn (Part $p) => CheckPart::dispatch($p));

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

        $this->info('Regenerating missing images');
        Part::unofficial()->lazy()->each(function (Part $p) {
            $image = str_replace('.dat', '.png', "library/unofficial/{$p->filename}");
            $thumb = str_replace('.png', '_thumb.png', $image);
            if (!Storage::disk('images')->exists($image) || !Storage::disk('images')->exists($thumb)) {
                UpdateImage::dispatch($p);
            }
        });

        OmrModel::lazy()->each(function (OmrModel $m) {
            $image = str_replace(['.dat','.mpd','.ldr'], '.png', "omr/models/{$m->filename()}");
            $thumb = str_replace('.png', '_thumb.png', $image);
            if (!Storage::disk('images')->exists($image) || !Storage::disk('images')->exists($thumb)) {
                UpdateImage::dispatch($m);
            }
        });

        $this->info('Reloading colors for LDConfig');
        $this->call('lib:update-colours');

        $this->info('Regenerate unofficial zip');
        $this->call('lib:refresh-zip');

        $this->info('Pruning failed jobs');
        $this->call('queue:prune-failed');
    }
}
