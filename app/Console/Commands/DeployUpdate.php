<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use Illuminate\Console\Command;

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
        $settings = new LibrarySettings();
        $header_metas = $settings->allowed_header_metas;
        if (!in_array('!PREVIEW', $header_metas)) {
            $header_metas[] = "!PREVIEW";
            $settings->allowed_header_metas = $header_metas;
            $settings->save();
        }
        Part::query()->update(['preview' => null]);
        Part::doesntHave('unofficial_part')
            ->lazy()
            ->each(function (Part $part) use ($settings) {
                if (array_key_exists(basename($part->filename, '.dat'), $settings->default_render_views)) {
                    $part->preview = '16 0 0 0 ' . $settings->default_render_views[basename($part->filename, '.dat')];
                } elseif (array_key_exists(basename($part?->base_part->filename ?? '', '.dat'), $settings->default_render_views)) {
                    $part->preview = '16 0 0 0 ' . $settings->default_render_views[basename($part->base_part->filename, '.dat')];
                } else {
                    return;
                }
                if (!$part->isUnofficial()) {
                    $part->has_minor_edit = true;
                }
                $part->generateHeader();
            });
        /*
        Part::query()->update(['rebrickable' => null]);
        $rb = app(\App\LDraw\Rebrickable::class);
        Part::partsFolderOnly()
            ->where('description', 'NOT LIKE', '~%')
            ->where('description', 'NOT LIKE', '\_%')
            ->where('description', 'NOT LIKE', '|%')
            ->where('description', 'NOT LIKE', '%(Obsolete)%')
            ->whereRelation('category', 'category', '<>', 'Sticker')
            ->whereRelation('category', 'category', '<>', 'Moved')
            ->lazy()
            ->each(function (Part $part) use ($rb) {
                $number = basename($part->filename, '.dat');
                $rb_num = Str::lower($part->keywords()->where('keyword', 'LIKE', "Rebrickable %")->first()?->keyword);
                $bl_num = Str::lower($part->keywords()->where('keyword', 'LIKE', "Bricklink %")->first()?->keyword);
                $rb_part = $rb->getParts(['ldraw_id' => $number])->first();
                if (is_null($rb_part) && Str::startsWith($rb_num, 'rebrickable')) {
                    $rb_num = Str::chopStart(Str::lower($rb_num), 'rebrickable ');
                    $rb_part = $rb->getPart($rb_num)->first();
                }
                if (is_null($rb_part) && Str::startsWith($bl_num, 'bricklink')) {
                    $bl_num = Str::chopStart(Str::lower($bl_num), 'bricklink ');
                    $rb_part = $rb->getParts(['bricklink_id' => $number])->first();
                }
                if (is_null($rb_part)) {
                    $this->info($number, $rb_num, $bl_num);
                    return;
                }
                $part->rebrickable = $rb_part;
                $part->save();
            });
        */
    }
}
