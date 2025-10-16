<?php

namespace App\Console\Commands;

use App\Services\LDraw\Rebrickable;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UpdateOmrData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update-omr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $rb = new Rebrickable();
        $themes = $rb->getThemes();

        $themes->each(function (array $theme) {
            Theme::updateOrCreate(
                [
                'id' => $theme['id']
            ],
                [
                'parent_id' => $theme['parent_id'],
                'name' => $theme['name']
            ]
            );
        });

        Set::all()->each(function (Set $set) use ($rb) {
            $rb_set = $rb->getSet($set->number)->all();
            if (Arr::get($rb_set, 'theme_id', '') == '') {
                dd($rb_set, $set->number);
            }
            if ($set->theme_id != $rb_set['theme_id']) {
                $set->theme_id = $rb_set['theme_id'];
                $set->save();
            }
        });
    }
}
