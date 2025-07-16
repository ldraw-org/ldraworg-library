<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (app()->environment() === 'local') {
                Route::prefix('dev')->name('dev.')->middleware(['web'])->group(base_path('routes/dev.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'currentlic' => App\Http\Middleware\CurrentLicense::class,
            'ldrawmember' => App\Http\Middleware\LdrawMember::class
        ]);
//        $middleware->web(append: [
//            App\Http\Middleware\LoginMybbUser::class,
//        ]);
        $middleware->encryptCookies(except: [
            'mybbuser',
        ]);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
