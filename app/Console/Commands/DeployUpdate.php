<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
    public function handle(): void
    {
        Part::where('description', 'LIKE', '~Stickerback %')
            ->each(function (Part $part) {
                $d = Str::of($part->description);
                $d = $d->replace('Stickerback', 'Sticker Back');
                if ($d->contains('Formed') && !$d->contains('(Formed)')) {
                    $d = $d->replace('Formed', '(Formed)');
                }
                $part->description = $d->toString();
                $part->save();
            });
    }
}
