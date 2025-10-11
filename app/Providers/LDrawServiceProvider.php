<?php

namespace App\Providers;

use App\Enums\PartType;
use App\Models\Mybb\MybbUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

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
        Stringable::macro('initials', function(){
            $words = preg_split("/\s+/", $this);
            $initials = "";
        
            foreach ($words as $w) {
              $initials .= $w[0];
            }
            if (strlen($initials) > 2) {
              $initials = $initials[0] . $initials[strlen($initials) - 1];
            }
            return new static($initials);
        });
        Str::macro('initials', function(string $string){
            return (string) (new Stringable($string))->initials();
        });
        Auth::viaRequest('mybb', function (Request $request) {
            return MybbUser::findFromCookie($request)?->library_user;
        });
    }
}
