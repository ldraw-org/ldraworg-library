<?php

namespace App\Observers;

use App\Enums\PreviewRotation;
use App\Events\PartSubmitted;
use App\Events\PartDeleted;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateImage;
use App\Jobs\UpdateLibraryCsv;
use App\Services\Part\GenerateHeader;
use App\Services\Part\Remover;
use App\Services\Part\SyncSubparts;
use App\Services\Part\SyncUnknownPartNumber;
use App\Services\Part\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class PartObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        protected SyncUnknownPartNumber $syncUnknownNumber,
        protected SyncSubparts $syncSubparts,
        protected Validator $validator,
        protected Remover $remover,
        protected GenerateHeader $generateHeader,
    ) {}

    public function deleting(Part $part): void
    {
        $this->remover->putDeletedBackup($part);
    }

    public function deleted(Part $part): void
    {
        $part->parents->each(function (Part $p) {
            $this->syncSubparts->loadSubparts($p);
            $this->validator->checkPart($p);
        });
        PartDeleted::dispatch(Auth::user() ?? User::find(1), $part->filename, $part->description);
    }

    public function saving(Part $part): void
    {
        if ($part->isDirty('help') && $part->help !== null && trim(implode('', $part->help)) === '') {
            $part->help = null;
        }
        if ($part->isDirty('cmdline') && $part->cmdline !== null && trim($part->cmdline) === '') {
            $part->cmdline = null;
        }
        if ($part->isDirty('preview') && $part->preview == null) {
            $part->preview = PreviewRotation::Default;
        }

        $headerRelevant = [
            'description',
            'filename',
            'user_id',
            'type',
            'type_qualifier',
            'part_release_id',
            'help',
            'category',
            'part_release_id',
            'bfc',
            'cmdline',
            'license',
            'preview'
        ];
        if ($part->isDirty($headerRelevant)) {
            $this->generateHeader->updatePartHeader($part);
        }
    }

    public function saved(Part $part): void
    {
        if ($part->wasChanged(['description', 'filename']) && $part->type->inPartsFolder() && $part->isNotFix()) {
            UpdateLibraryCsv::dispatch();
        }

        if ($part->wasChanged('filename')) {
            $this->syncUnknownNumber->handle($part);
        }

        if($part->wasChanged('preview')) {
            GeneratePartImage::dispatch($part->id);
        }
    }

    public function pivotAttached(Part $part, $relationName): void
    {
        if ($relationName === 'keywords') {
            $this->generateHeader->updatePartHeader($part);
            $part->saveQuietly();
        }
    }

    public function pivotDetached(Part $part, $relationName): void
    {
        if ($relationName === 'keywords') {
            $this->generateHeader->updatePartHeader($part);
            $part->saveQuietly();
        }
    }
}
