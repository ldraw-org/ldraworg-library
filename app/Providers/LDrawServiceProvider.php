<?php

namespace App\Providers;

use App\LDraw\LDrawModelMaker;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use App\LDraw\Parse\Parser;
use App\LDraw\Rebrickable;
use App\LDraw\Render\LDView;
use App\Settings\LibrarySettings;
use Illuminate\Database\Eloquent\Collection;

class LDrawServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Parser::class, function (Application $app) {
            return new Parser(
                config('ldraw.patterns'),
                \App\Models\Part\PartType::pluck('type')->all(),
                \App\Models\Part\PartTypeQualifier::pluck('type')->all(),
                $app->make(LibrarySettings::class),
            );
        });

        $this->app->bind(LDView::class, function (Application $app) {
            return new LDView(
                config('ldraw.ldview_debug'),
                $app->make(LibrarySettings::class),
                new LDrawModelMaker()
            );
        });
        $this->app->bind(Rebrickable::class, function (Application $app) {
            return new Rebrickable(
                config('ldraw.rebrickable_api_key'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Collection::macro('unofficial', fn (): Collection => $this->whereNull('part_release_id'));
        Collection::macro('official', fn (): Collection => $this->whereNotNull('part_release_id'));
    }
}
