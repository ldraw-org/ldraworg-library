<?php

namespace App\Providers;

use App\Enums\PartType;
use App\Models\Mybb\MybbUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
        Auth::viaRequest('mybb', function (Request $request) {
            return MybbUser::findFromCookie($request)?->library_user;
        });
    }
}
