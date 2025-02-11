<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MybbUser;
use App\Models\User;

class LoginMybbUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            // Get the mybb login data from the mybbuser cookie
            $mybb = MybbUser::findFromCookie();

            if (!is_null($mybb)) {
                // Check if the logged in user matches a user in the library db
                $usr = User::firstWhere('forum_user_id', $mybb->uid);
                if (is_null($usr)) {
                    return $next($request);
                }
                // Log the mybb user in since checking the mybb db every time is slow
                Auth::login($usr, true);
            }
        }
        return $next($request);
    }
}
