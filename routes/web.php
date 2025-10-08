<?php

use App\Enums\PartType;
use App\Http\Controllers\DocumentIndexController;
use App\Http\Controllers\DocumentShowController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportFilesController;
use App\Http\Controllers\Part\LastDayDownloadZipController;
use App\Http\Controllers\Part\LatestPartsController;
use App\Http\Controllers\Part\LatestReleaseController;
use App\Http\Controllers\Part\PartUpdateController;
use App\Http\Controllers\Part\PartDownloadController;
use App\Http\Controllers\Part\WeeklyPartsController;
use App\Http\Controllers\ReviewSummaryController;
use App\Http\Controllers\StickerSheetShowController;
use App\Http\Controllers\TrackerHistoryController;
use App\Livewire\Dashboard\Admin\Index as AdminIndex;
use App\Livewire\Dashboard\Admin\Pages\DocumentCategoryManagePage;
use App\Livewire\Dashboard\Admin\Pages\DocumentManagePage;
use App\Livewire\Dashboard\Admin\Pages\LdconfigEdit;
use App\Livewire\Dashboard\Admin\Pages\ReviewSummaryManagePage;
use App\Livewire\Dashboard\Admin\Pages\RoleManagePage;
use App\Livewire\Dashboard\Admin\Pages\UserManagePage;
use App\Livewire\Dashboard\Admin\Pages\LibrarySettingsPage;
use App\Livewire\Dashboard\Admin\Pages\PartKeywordManagePage;
use App\Livewire\Dashboard\User\Index as UserIndex;
use App\Livewire\JoinLdraw;
use App\Livewire\LDrawModelViewer;
use App\Livewire\Omr\OmrModel\Add;
use App\Livewire\Omr\Set\Index;
use App\Livewire\Omr\Set\Show as SetShow;
use App\Livewire\Part\Show;
use App\Livewire\Part\Submit;
use App\Livewire\Part\Weekly;
use App\Livewire\PartEvent\Index as PartEventIndex;
use App\Livewire\PbgGenerator;
use App\Livewire\Poll\Show as PollShow;
use App\Livewire\Release\Create;
use App\Livewire\Search\Suffix;
use App\Livewire\TorsoShortcutHelper;
use App\Livewire\Tracker\ConfirmCA;

Route::view('/', 'index')->name('index');

// Rate limited Routes
Route::middleware(['throttle:file'])->group(function () {
    Route::get('/webgl/part/{part}', [SupportFilesController::class, 'webglpart'])->name('webgl.part');
    Route::get('/webgl/omr/{omrmodel}', [SupportFilesController::class, 'webglmodel'])->name('webgl.model');
    Route::get('/categories.txt', [SupportFilesController::class, 'categories'])->name('categories-txt');
    Route::get('/library.csv', [SupportFilesController::class, 'librarycsv'])->name('library-csv');
    Route::get('/ptreleases/{output}', [SupportFilesController::class, 'ptreleases'])->name('ptreleases');
    Route::get('/tracker/latest-parts', LatestPartsController::class)->name('part.latest');
    Route::get('/tracker/weekly-parts', WeeklyPartsController::class)->name('part.weekly-api');
    Route::get('/tracker/ldrawunf-last-day.zip', LastDayDownloadZipController::class)->name('tracker.last-day');
    Route::get('/update/latest', LatestReleaseController::class)->name('update.latest');
    Route::get('/library/{library}/{filename}', PartDownloadController::class)
        ->whereIn('library', ['official', 'unofficial'])
        ->where('filename', '(' . implode('|', PartType::folders()) . ')/[a-z0-9_-]+\.(dat|png|zip)')
        ->name('part.download');
});

// Tools
Route::get('/model-viewer', LDrawModelViewer::class)->name('model-viewer');
Route::get('/pbg', PbgGenerator::class)->name('pbg');

Route::view('/icons', 'icon-demo')->name('icon-demo');

Route::get('/joinldraw', JoinLdraw::class)->name('joinldraw');

Route::middleware(['ldrawmember'])->group(function () {
    Route::view('/polls', 'poll.index')->can('voteAny', App\Models\Poll\Poll::class)->name('poll.index');
    Route::get('/polls/{poll}', PollShow::class)->can('vote', 'poll')->name('poll.show');
});

// Updates
Route::get('/updates', [PartUpdateController::class, 'index'])->name('part-update.index');
Route::get('/updates/view{release:short}', [PartUpdateController::class, 'view'])->name('part-update.view');

Route::prefix('parts')->name('parts.')->group(function () {
    Route::view('/list', 'part.index')->name('list');
    Route::view('/category-list', 'part.category')->name('category.list');

    // Stickers
    Route::view('/sticker-sheets', 'sticker-sheet.index')->name('sticker-sheet.index');
    Route::get('/sticker-sheets/{sheet}', StickerSheetShowController::class)->name('sticker-sheet.show');

    // Search
    Route::get('/search/suffix', Suffix::class)->name('search.suffix');

    Route::get('/{part}', Show::class)->name('show');

    Route::get('/{filename}', Show::class)
        ->where('filename', '(unofficial/)?(' . implode('|', PartType::folders()) . ')/([a-z0-9_-]+\.(dat|png))')
        ->name('show.file');

});

// Users
Route::view('/users', 'user.index')->name('users.index');

Route::prefix('tracker')->name('tracker.')->group(function () {
    Route::view('/', 'tracker.main')->name('main');

    Route::middleware(['auth', 'currentlic'])->group(function () {
        Route::get('/submit', Submit::class)->name('submit');
        Route::get('/torso-helper', TorsoShortcutHelper::class)->name('torso-helper');
    });

    Route::get('/weekly', Weekly::class)->name('weekly');
    Route::get('/history', TrackerHistoryController::class)->name('history');
    Route::get('/summary/{summary}', ReviewSummaryController::class)->name('summary.view');
    Route::middleware(['auth'])->get('/confirmCA', ConfirmCA::class)->name('confirmCA.show');

    Route::get('/activity', PartEventIndex::class)->name('activity');

    Route::view('/next-release', 'part.nextrelease')->name('next-release');

    Route::middleware(['auth'])->get('/release/create', Create::class)->name('release.create');
});

Route::prefix('omr')->name('omr.')->group(function () {
    Route::view('/', 'omr.main')->name('main');
    Route::get('/sets', Index::class)->name('sets.index');
    Route::get('sets/{set}', SetShow::class)->name('sets.show');
    Route::get('/set/{setnumber}', SetShow::class)->name('show.setnumber');
    Route::middleware(['auth'])->get('/add', Add::class)->name('add');
});


Route::prefix('documentation')->name('documentation.')->group(function () {
    Route::get('/', DocumentIndexController::class)->name('index');
    Route::get('/{document_category}/{document}', DocumentShowController::class)->name('show');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminIndex::class)->name('index');
    Route::get('/users', UserManagePage::class)->name('users.index');
    Route::get('/summaries', ReviewSummaryManagePage::class)->name('summaries.index');
    Route::get('/roles', RoleManagePage::class)->name('roles.index');
    Route::get('/documents', DocumentManagePage::class)->name('documents.index');
    Route::get('/document-categories', DocumentCategoryManagePage::class)->name('document-categories.index');
    Route::get('/part-keywords', PartKeywordManagePage::class)->name('part-keywords.index');
    Route::get('/settings', LibrarySettingsPage::class)->name('settings.index');
    Route::get('/ldconfig', LdconfigEdit::class)->name('ldconfig.index');
});


Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', UserIndex::class)->name('index');
});

Route::middleware(['auth'])->get('/logout', function () {
    auth()->logout();
    return back();
});

// permanentRedirects
Route::name('permanentRedirects.')->group(function () {
    Route::permanentRedirect('/login', 'https://forums.ldraw.org/member.php?action=login')->name('login');
    Route::permanentRedirect('/docs', 'https://www.ldraw.org/docs-main.html')->name('doc');

    Route::permanentRedirect('/official/search', '/parts/list')->name('official.search');
    Route::permanentRedirect('/official/suffixsearch', '/search/suffix')->name('official.suffix');
    Route::permanentRedirect('/official/list', '/parts/list')->name('official.list');
    Route::permanentRedirect('/official/{part:id}', 'parts/{part:id}')->name('official.part.show');
    Route::permanentRedirect('/official/{partfile}', '/parts/{partfile}')->name('official.part.name');

    Route::permanentRedirect('/ptreleases', '/ptreleases/tab')->name('ptreleases');

    Route::permanentRedirect('/search', '/parts/list')->name('search');
    Route::permanentRedirect('/search/part', '/parts/list')->name('search.part');
    Route::permanentRedirect('/search/sticker', '/sticker-sheets')->name('search.sticker');
    Route::permanentRedirect('/search/suffix', '/parts/search/suffix')->name('search.suffix');

    Route::permanentRedirect('/sticker-sheets', '/parts/sticker-sheets')->name('sticker-sheets.index');
    Route::permanentRedirect('/sticker-sheets/{sheet}', '/parts/sticker-sheets/{sheet}')->name('sticker-sheet.show');

    Route::permanentRedirect('/tracker/list', '/parts/list')->name('tracker.list');
    Route::permanentRedirect('/tracker/search', '/parts/list')->name('tracker.search');
    Route::permanentRedirect('/tracker/suffixsearch', '/search/suffix')->name('tracker.suffix');
    Route::permanentRedirect('/tracker/{part:id}', '/parts/{part:id}')->name('tracker.part.show');
    Route::permanentRedirect('/tracker/{partfile}', '/parts/{partfile}')->name('tracker.part.name');
});
