<?php

namespace App\Models\Part;

use App\Enums\ReleaseStage;
use App\Models\Traits\HasPartRelease;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The current, or most recent, parts release and its progress. Only one row is ever kept.
 */
#[Unguarded]
class PartReleaseRun extends Model
{
    use HasFactory;
    use HasPartRelease;
    use HasUser;

    /** The id of the single kept row */
    public const int CURRENT_ID = 1;

    /**
     * @return array{stage: 'App\Enums\ReleaseStage'}
     */
    protected function casts(): array
    {
        return [
            'stage' => ReleaseStage::class,
        ];
    }

    #[Scope]
    protected function running(Builder $query): void
    {
        $query->where('stage', '!=', ReleaseStage::Complete)->whereNull('error');
    }

    #[Scope]
    protected function complete(Builder $query): void
    {
        $query->where('stage', ReleaseStage::Complete);
    }

    /**
     * Running or failed: either way no new release may start.
     */
    #[Scope]
    protected function incomplete(Builder $query): void
    {
        $query->where('stage', '!=', ReleaseStage::Complete);
    }

    public function isRunning(): bool
    {
        return $this->stage !== ReleaseStage::Complete && is_null($this->error);
    }

    public function hasFailed(): bool
    {
        return !is_null($this->error);
    }
}
