<?php

namespace App\Console\Commands;

use App\Enums\PartError;
use App\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
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
        Part::with('ancestors','ancestors.keywords')
            ->hasError(PartError::NoSetKeywordForPattern)
            ->has('ancestors')
            ->each(function (Part $part) {
                $kw = null;
                foreach($part->ancestors->unique() as $ancestor) {
                    $kw = $ancestor->keywords?->first(fn (PartKeyword $kw) => Str::of($kw->keyword)->startsWith(['Set ', 'set ']));
                    if (!is_null($kw)) {
                        $part->keywords()->syncWithoutDetaching([$kw]);
                        $part->load('keywords');
                        unset($part->part_check_messages['errors'][PartError::NoSetKeywordForPattern->value]);
                        $part->generateHeader();
                        break;
                    }
                }
            });
    }
}
