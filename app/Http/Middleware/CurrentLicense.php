<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CurrentLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
      if (Auth::user()->license->id != \App\Models\PartLicense::defaultLicense()->id) {
        session(['ca_route_redirect' => $request->route()->getName()]);
        return redirect('tracker/confirmCA');
      }

      return $next($request);
    }
}
