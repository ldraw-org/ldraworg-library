<?php

namespace App\Http\Middleware;

use App\Models\MybbUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LdrawMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $u = MybbUser::findFromCookie();
        if (is_null($u) || !$u->inGroup(config('ldraw.mybb-groups')['LDraw Member'])) {
            session(['mem_route_redirect' => $request->route()->getName()]);
            return redirect('joinldraw');
        }
        return $next($request);
    }
}
