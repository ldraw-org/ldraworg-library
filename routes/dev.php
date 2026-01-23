<?php

use App\Livewire\FileEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['can:edit-files'])->get('/ace', FileEditor::class)->name('ace');

Route::middleware(['can:assume-user'])->get('/login-user-{number}', function (int $number) {
    auth()->logout();
    auth()->login(\App\Models\User::find($number));
    return back();
});

Route::get('/daily-digest', function () {
    return new App\Mail\DailyDigest(auth()->user());
});

Route::view('/test-table', 'tracker.testtable');

Route::get('/local-login',  fn() => view('local-login'))->name('local-login');

Route::post('/local-login', function (Request $request) {
    $credentials = $request->only('name', 'password');
    if (auth()->attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
    return back()->withErrors(['name' => 'Invalid']);
});
