<?php

use App\Livewire\FileEditor;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\Route;

Route::get('/daily-digest', function () {
    return new App\Mail\DailyDigest(auth()->user());
});

Route::view('/test-table', 'tracker.testtable');
