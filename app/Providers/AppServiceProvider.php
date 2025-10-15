<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Listeners\PartEventSubscriber;
use App\Models\Omr\Set;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\Records\QueuedJob;
use Pan\PanConfiguration;
use Spatie\Permission\Models\Role;

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
        Route::pattern('setnumber', '[a-z0-9]+(-\d+)?');

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


        // Allow Super Users full access excluding voting
        Gate::before(function (User $user, string $ability) {
            return !in_array($ability, ['vote', 'allCertify', 'allAdmin']) && $user->hasRole('Super Admin') ? true : null;
        });

        // Manually register Role policy
        Gate::policy(Role::class, RolePolicy::class);

        Gate::define('viewPulse', function (User $user) {
            return $user->can(Permission::PulseView);
        });

        //Subscriber
        Event::subscribe(PartEventSubscriber::class);

        Nightwatch::rejectQueuedJobs(function (QueuedJob $queuedjob) {
            return true; // Reject all queued job for now
        });
    }
}
