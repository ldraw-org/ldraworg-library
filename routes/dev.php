<?php

use App\Livewire\FileEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::middleware(['can:edit-files'])->get('/ace', FileEditor::class)->name('ace');

Route::get('/daily-digest', function () {
    return new App\Mail\DailyDigest(auth()->user());
});

Route::view('/test-table', 'tracker.testtable');
