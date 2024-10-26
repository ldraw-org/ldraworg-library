<?php

use App\Http\Controllers\DocumentShowController;
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
use App\Http\Controllers\StickerSheet\StickerSheetShowController;
use App\Http\Controllers\TrackerHistoryController;
use App\Livewire\Dashboard\Admin\Index as AdminIndex;
use App\Livewire\Dashboard\Admin\Pages\DocumentCategoryManagePage;
use App\Livewire\Dashboard\Admin\Pages\DocumentManagePage;
use App\Livewire\Dashboard\Admin\Pages\ReviewSummaryManagePage;
use App\Livewire\Dashboard\Admin\Pages\RoleManagePage;
use App\Livewire\Dashboard\Admin\Pages\UserManagePage;
use App\Livewire\Dashboard\Admin\Pages\LibrarySettingsPage;
use App\Livewire\Dashboard\Admin\Pages\PartCategoryManagePage;
use App\Livewire\Dashboard\Admin\Pages\PartKeywordManagePage;
use App\Livewire\Dashboard\Admin\Pages\PartLicenseManagePage;
use App\Livewire\Dashboard\Admin\Pages\PartTypeManagePage;
use App\Livewire\Dashboard\User;
use App\Livewire\LDrawModelViewer;
use App\Livewire\Omr\Set\Index;
use App\Livewire\Part\Index as PartIndex;
use App\Livewire\Part\Show;
use App\Livewire\Part\Submit;
use App\Livewire\Part\Weekly;
use App\Livewire\PartEvent\Index as PartEventIndex;
use App\Livewire\PbgGenerator;
use App\Livewire\Release\Create;
use App\Livewire\Search\Parts;
use App\Livewire\Search\Suffix;
use App\Livewire\Tables\PartListTable;
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
    Route::get('/library/official/{officialpart}', PartDownloadController::class)->name('official.download');
    Route::get('/library/official/{officialpartzip}', PartDownloadZipController::class)->name('official.download.zip');
    Route::get('/library/unofficial/{unofficialpart}', PartDownloadController::class)->name('unofficial.download');
    Route::get('/library/unofficial/{unofficialpartzip}', PartDownloadZipController::class)->name('unofficial.download.zip');
});

Route::get('/pbg', PbgGenerator::class)->name('pbg');
Route::get('/model-viewer', LDrawModelViewer::class)->name('model-viewer');
Route::view('/sticker-sheets', 'sticker-sheet.index')->name('sticker-sheet.index');
Route::get('/sticker-sheets/{sheet}', StickerSheetShowController::class)->name('sticker-sheet.show');

Route::prefix('tracker')->name('tracker.')->group(function () {
    Route::view('/', 'tracker.main')->name('main');

    Route::middleware(['auth', 'currentlic'])->get('/submit', Submit::class)->name('submit');

    Route::get('/list', PartListTable::class)->name('index');
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


Route::prefix('documentation')->name('documentation.')->group(function () {
    Route::view('/', 'documents.index')->name('index');
    Route::get('/{document:nav_title}', DocumentShowController::class, 'show')->name('show');
    Route::get('/{document}', DocumentShowController::class, 'show')->name('show');
});

Route::middleware(['auth', 'can:admin.view-dashboard'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminIndex::class)->name('index');
    Route::middleware(['can:create,App\Models\Users'])->get('/users', UserManagePage::class)->name('users.index');
    Route::middleware(['can:viewAny,App\Models\ReviewSummary\ReviewSummary'])->get('/summaries', ReviewSummaryManagePage::class)->name('summaries.index');
    Route::middleware(['can:create,App\Models\Role'])->get('/roles', RoleManagePage::class)->name('roles.index');
    Route::middleware(['can:create,App\Models\PartLicense'])->get('/part-licenses', PartLicenseManagePage::class)->name('part-licenses.index');
    Route::middleware(['can:documentation.edit'])->get('/documents', DocumentManagePage::class)->name('documents.index');
    Route::middleware(['can:documentation.edit'])->get('/document-categories', DocumentCategoryManagePage::class)->name('document-categories.index');
    Route::middleware(['can:create,App\Models\PartCategory'])->get('/part-categories', PartCategoryManagePage::class)->name('part-categories.index');
    Route::middleware(['can:manage,App\Models\PartKeyword'])->get('/part-keywords', PartKeywordManagePage::class)->name('part-keywords.index');
    Route::middleware(['can:create,App\Models\PartType'])->get('/part-types', PartTypeManagePage::class)->name('part-types.index');
    Route::middleware(['can:settings.edit'])->get('/settings', LibrarySettingsPage::class)->name('settings.index');
});


Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', User::class)->name('index');
});


Route::get('/updates', [PartUpdateController::class, 'index'])->name('part-update.index');
Route::get('/updates/view{release:short}', [PartUpdateController::class, 'view'])->name('part-update.view');

Route::redirect('/search', '/search/part');
Route::get('/search/part', Parts::class)->name('search.part');
Route::get('/search/suffix', Suffix::class)->name('search.suffix');
Route::redirect('/search/sticker', '/sticker-sheets');

Route::prefix('official')->name('official.')->group(function () {
    Route::redirect('/search', '/search/part');
    Route::redirect('/suffixsearch', '/search/suffix');
    Route::get('/list', PartListTable::class)->name('index');
    Route::get('/{officialpart}', Show::class)->name('show.filename');
    Route::get('/{part}', Show::class)->name('show');
});

Route::redirect('/login', 'https://forums.ldraw.org/member.php?action=login');
Route::redirect('/docs', 'https://www.ldraw.org/docs-main.html')->name('doc');

Route::middleware(['auth'])->get('/logout', function () {
    auth()->logout();
    return back();
});
