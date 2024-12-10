<?php

namespace App\Providers;

use App\Enums\PartType;
use Illuminate\Support\ServiceProvider;
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
        Collection::macro('partsFolderOnly', fn (): Collection => $this->whereIn('type', PartType::partsFolderTypes()));
    }
}
