<?php

namespace App\Providers;

use App\Listeners\PartEventSubscriber;
use App\Models\Omr\Set;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Pan\PanConfiguration;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        PanConfiguration::maxAnalytics(200);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());
        // Model::preventLazyLoading(! $this->app->isProduction());

        // Route bindings
        $namePattern = '[a-z0-9_/-]+';
        $filenamePattern = "{$namePattern}\.(dat|png)";
        $zipPattern = "{$namePattern}\.(zip)";
        Route::pattern('partfile', $filenamePattern);
        Route::pattern('upartfile', $filenamePattern);
        Route::pattern('officialpartzip', '[a-z0-9_/-]+\.zip');
        Route::pattern('unofficialpart', '[a-z0-9_/-]+\.(dat|png)');
        Route::pattern('unofficialpartzip', '[a-z0-9_/-]+\.zip');
        Route::pattern('setnumber', '[a-z0-9]+(-\d+)?');
        Route::bind(
            'partfile',
            function (string $value): Part {
                if(Part::where('filename', $value)->count() > 1) {
                    return Part::official()->where('filename', $value)->firstOrFail();
                }
                return Part::where('filename', $value)->firstOrFail();
            }
        );
        Route::bind(
            'upartfile',
            fn (string $value): Part =>
                Part::unofficial()->where('filename', $value)->firstOrFail()
        );
        Route::bind(
            'officialpartzip',
            fn (string $value): Part =>
            Part::official()
                ->where('filename', str_replace('.zip', '.dat', $value))
                ->firstOrFail()
        );
        Route::bind(
            'unofficialpartzip',
            fn (string $value): Part =>
            Part::unofficial()
                ->where('filename', str_replace('.zip', '.dat', $value))
                ->firstOrFail()
        );
        Route::bind(
            'setnumber',
            fn (string $value): Set =>
            Set::where(
                fn (Builder $q) =>
                $q->orWhere('number', $value)->orWhere('number', "{$value}-1")
            )
            ->firstOrFail()
        );

        // Rate limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('file', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });


        // Allow Super Users full access
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        //Subscriber
        Event::subscribe(PartEventSubscriber::class);
    }
}
