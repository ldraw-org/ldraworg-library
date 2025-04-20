<?php

namespace App\Models\Mybb;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MybbPost extends Model
{
    protected $table = 'mybb_posts';
    protected $primaryKey = 'pid';
    public $timestamps = false;
    protected $connection = 'mybb';

    public function user(): BelongsTo
    {
        return $this->belongsTo(MybbUser::class, 'uid', 'uid');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MybbAttachment::class, 'pid', 'pid');
    }

    #[Scope]
    protected function unchecked(Builder $query): void
    {
        $query->has('attachments')
            ->where('fid', config('ldraw.mybb_omr.omr_forum_id'))
            ->where('icon', '!=', config('ldraw.mybb_omr.checked_icon_id'));
    }

}
