<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
      if (is_null($request->user)) {
        // Get the mybb login data from the mybbuser cookie
        if ($mybb = $request->cookies->get('mybbuser')) {
          $mybb = explode("_", $mybb);
          // The cookie should be in the format <uid>_<loginkey>
          if (!is_array($mybb) || count($mybb) !== 2 || !is_numeric($mybb[0])) $next($request);
          // Look up the mybb user in the database
          $u = DB::connection('mybb')->table('mybb_users')
                ->select('uid')
                ->where('uid', $mybb[0])->where('loginkey', $mybb[1])->first();
          if (empty($u)) $next($request);

          // Check if the logged in user matches a user in the library db
          $usr = Auth::getProvider()->retrieveByCredentials(['forum_user_id' => $u->uid]);
          if (empty($usr)) $next($request);

          // Log the mybb user in since checking the mybb db every time is slow
          Auth::login($usr, true);
          $request->user = Auth::user();
        }
      }
      return $next($request);
    }
}
