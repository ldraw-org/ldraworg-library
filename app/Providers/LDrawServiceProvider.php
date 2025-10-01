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
        Auth::viaRequest('mybb', function (Request $request) {
            return MybbUser::findFromCookie($request)?->library_user;
        });
    }
}
