<?php

use App\Livewire\FileEditor;
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

Route::middleware(['can:view-test-table'])->view('/test-table', 'tracker.testtable');
