<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Services\LDraw\Parse\Parser;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\TemporaryDirectory\TemporaryDirectory;

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
        PartRelease::latest()->each(function (PartRelease $release) {
            if ($release->total != 0) {
                return;
            }
            if (Storage::disk('library')->exists("official/models/Note{$release->short}CA.txt")) {
                $this->info("Processing {$release->short}");
                $data = [];
                $file = Parser::unixLineEndings(Storage::disk('library')->get("official/models/Note{$release->short}CA.txt"));
                preg_match('#^(\h+)?Total files:\h+(?<total>[0-9]+)(.*)#um', $file, $matches);
                $data['total'] = $matches['total'];
                preg_match('#^(\h+)?New files:\h+(?<new>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new'] = $matches['new'];
                $data['new_of_type'] = [];
                foreach (PartType::cases() as $t) {
                    if ($t == PartType::Shortcut) {
                        continue;
                    }
                    $data['new_of_type'][$t->value] = 0;
                }
                preg_match('#^(\h+)?New parts:\h+(?<parts>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new_of_type'][PartType::Part->value] = Arr::get($matches, 'parts',  0);
                preg_match('#^(\h+)?New subparts:\h+(?<subparts>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new_of_type'][PartType::Subpart->value] = Arr::get($matches, 'subparts', 0);
                preg_match('#^(\h+)?New primitives:\h+(?<prim>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new_of_type'][PartType::Primitive->value] = Arr::get($matches, 'prim', 0);
                preg_match('#^(\h+)?New lo-res primitives:\h+(?<loprim>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new_of_type'][PartType::LowResPrimitive->value] = Arr::get($matches, 'loprim', 0);
                preg_match('#^(\h+)?New hi-res primitives:\h+(?<hiprim>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new_of_type'][PartType::HighResPrimitive->value] = Arr::get($matches, 'hiprim', 0);
                preg_match('#^(\h+)?New part texture images:\h+(?<tex>[0-9]+)(\h+)?$#um', $file, $matches);
                $data['new_of_type'][PartType::PartTexmap->value] = Arr::get($matches, 'tex', 0);
                $release->fill($data);
                $release->save();
            }
        });
    }
}
