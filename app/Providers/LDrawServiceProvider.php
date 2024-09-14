<?php

namespace App\Providers;

use App\LDraw\Check\PartChecker;
use App\LDraw\LDrawModelMaker;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use App\LDraw\Parse\Parser;
use App\LDraw\PartManager;
use App\LDraw\Rebrickable;
use App\LDraw\Render\LDView;
use App\Models\Part;
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
        $this->app->singleton(PartChecker::class, function (Application $app) {
            return new PartChecker($app->make(LibrarySettings::class));
        });

        $this->app->singleton(Parser::class, function (Application $app) {
            return new Parser(
                config('ldraw.patterns'),
                \App\Models\PartType::pluck('type')->all(),
                \App\Models\PartTypeQualifier::pluck('type')->all(),
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

        $this->app->singleton(PartManager::class, function (Application $app) {
            return new PartManager(
                $app->make(Parser::class),
                $app->make(LDView::class),
                $app->make(LibrarySettings::class),
            );
        });

        $this->app->singleton(Rebrickable::class, function (Application $app) {
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
        Collection::macro(
            'patterns',
            fn (?string $basepart = null): Collection =>
            $this->where('type.folder', 'parts/')
                ->filter(fn (Part $p) => preg_match('/^parts\/' . (is_null($basepart) ? $p->basepart() : $basepart) . 'p(?:[a-z0-9]{2,3}|[0-9]{4})\.dat$/ui', $p->filename) === 1)
        );
        Collection::macro(
            'composites',
            fn (?string $basepart = null): Collection =>
            $this->where('type.folder', 'parts/')
                ->filter(fn (Part $p) => preg_match('/^parts\/' . (is_null($basepart) ? $p->basepart() : $basepart) . 'c(?:[a-z0-9]{2}|[0-9]{4})(?:-f[0-9])?\.dat/ui', $p->filename) === 1)
        );
        Collection::macro(
            'sticker_shortcuts',
            fn (?string $basepart = null): Collection =>
            $this->where('type.folder', 'parts/')
                ->where('category.category', 'Sticker Shortcut')
                ->filter(fn (Part $p) => preg_match('/^parts\/' . (is_null($basepart) ? $p->basepart() : $basepart) . '(?:p(?:[a-z0-9]{2,3}|[0-9]{4})|c(?:[a-z0-9]{2}|[0-9]{4})(?:-f[0-9])?|d(?:[a-z0-9]{2}|[0-9]{4}))\.dat/ui', $p->filename) === 1)
        );
    }
}
