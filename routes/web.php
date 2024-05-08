<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportFilesController;
use App\Http\Controllers\Omr\SetController;
use App\Http\Controllers\Part\LastDayDownloadZipController;
use App\Http\Controllers\Part\LatestPartsController;
use App\Http\Controllers\Part\PartUpdateController;
use App\Http\Controllers\Part\PartDownloadController;
use App\Http\Controllers\Part\PartDownloadZipController;
use App\Http\Controllers\Part\PartWebGLController;
use App\Http\Controllers\ReviewSummaryController;
use App\Http\Controllers\TrackerHistoryController;
use App\Livewire\Dashboard\Admin\Index as AdminIndex;
use App\Livewire\Dashboard\Admin\Pages\PartRenderViewManagePage;
use App\Livewire\Dashboard\Admin\Pages\ReviewSummaryManagePage;
use App\Livewire\Dashboard\Admin\Pages\RoleManagePage;
use App\Livewire\Dashboard\Admin\Pages\UserManagePage;
use App\Livewire\Dashboard\User;
use App\Livewire\Omr\Set\Index;
use App\Livewire\Part\Index as PartIndex;
use App\Livewire\Part\Show;
use App\Livewire\Part\Submit;
use App\Livewire\Part\Weekly;
use App\Livewire\PartEvent\Index as PartEventIndex;
use App\Livewire\PbgGenerator;
use App\Livewire\Release\Create;
use App\Livewire\Search\Parts;
use App\Livewire\Search\StickerSummary;
use App\Livewire\Search\Suffix;
use App\Livewire\Tracker\ConfirmCA;

Route::view('/', 'index')->name('index');

// Rate limited Routes
Route::middleware(['throttle:file'])->group(function () {
    Route::get('/categories.txt', [SupportFilesController::class, 'categories'])->name('categories-txt');
    Route::get('/library.csv', [SupportFilesController::class, 'librarycsv'])->name('library-csv');
    Route::get('/ptreleases/{output}', [SupportFilesController::class, 'ptreleases'])->name('ptreleases');
    Route::redirect('/ptreleases', '/ptreleases/tab');
    Route::get('/ldbi/part/{part}', PartWebGLController::class)->name('part.ldbi');    
    Route::get('/tracker/latest-parts', LatestPartsController::class)->name('part.latest');
    Route::get('/tracker/ldrawunf-last-day.zip', LastDayDownloadZipController::class)->name('tracker.last-day');
});

Route::get('/pbg', PbgGenerator::class)->name('pbg');

Route::prefix('tracker')->name('tracker.')->group(function () {
    Route::view('/', 'tracker.main')->name('main');

    Route::middleware(['auth', 'currentlic'])->get('/submit', Submit::class)->name('submit');

    Route::get('/list', PartIndex::class)->name('index');
    Route::get('/weekly', Weekly::class)->name('weekly');
    Route::get('/history', TrackerHistoryController::class)->name('history');
    Route::get('/summary/{summary}', ReviewSummaryController::class)->name('summary.view');

    Route::middleware(['auth'])->get('/confirmCA', ConfirmCA::class)->name('confirmCA.show');

    Route::redirect('/search', '/search/part');
    Route::redirect('/suffixsearch', '/search/suffix');

    Route::get('/activity', PartEventIndex::class)->name('activity');

    Route::view('/next-release', 'part.nextrelease')->name('next-release');

    Route::middleware(['can:release.create'])->get('/release/create', Create::class)->name('release.create');
    
    Route::get('/{unofficialpart}', Show::class)->name('show.filename');
    Route::get('/{part}', Show::class)->name('show');
});

Route::prefix('omr')->name('omr.')->group(function () {
    Route::view('/', 'omr.main')->name('main');
    Route::get('/sets', Index::class)->name('sets.index');
    Route::resource('sets', SetController::class)->only(['show']);
    Route::get('/set/{setnumber}', [SetController::class, 'show'])->name('show.setnumber');
});


Route::middleware(['auth', 'can:admin.view-dashboard'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminIndex::class)->name('index');
    Route::get('/users', UserManagePage::class)->name('users.index');
    Route::middleware(['can:viewAny,App\Models\ReviewSummary'])->get('/summaries', ReviewSummaryManagePage::class)->name('summaries.index');
    Route::middleware(['can:viewAny,App\Models\Role'])->get('/roles', RoleManagePage::class)->name('roles.index');
    Route::middleware(['can:library.settings.edit'])->get('/part-render-views', PartRenderViewManagePage::class)->name('part-render-views.index');
});


Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', User::class)->name('index');
});


Route::get('/updates', [PartUpdateController::class, 'index'])->name('part-update.index');
Route::get('/updates/view{release:short}', [PartUpdateController::class, 'view'])->name('part-update.view');

Route::redirect('/search', '/search/part');
Route::get('/search/part', Parts::class)->name('search.part');
Route::get('/search/suffix', Suffix::class)->name('search.suffix');
Route::get('/search/sticker', StickerSummary::class)->name('search.sticker');

Route::prefix('official')->name('official.')->group(function () {
    Route::redirect('/search', '/search/part');
    Route::redirect('/suffixsearch', '/search/suffix');
    Route::get('/list', PartIndex::class)->name('index');
    Route::get('/{officialpart}', Show::class)->name('show.filename');
    Route::get('/{part}', Show::class)->name('show');
});

Route::redirect('/login', 'https://forums.ldraw.org/member.php?action=login');
Route::redirect('/documentation', 'https://www.ldraw.org/docs-main.html')->name('doc');

Route::middleware(['throttle:file'])->get('/library/official/{officialpart}', PartDownloadController::class)->name('official.download');
Route::middleware(['throttle:file'])->get('/library/unofficial/{unofficialpart}', PartDownloadController::class)->name('unofficial.download');
Route::middleware(['throttle:file'])->get('/library/unofficial/{unofficialpartzip}', PartDownloadZipController::class)->name('unofficial.download.zip');

Route::middleware(['auth'])->get('/logout', function () {
    auth()->logout();
    return back();
});



