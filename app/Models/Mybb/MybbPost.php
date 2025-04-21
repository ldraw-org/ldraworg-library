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

    public function url(): string
    {
        return "https://forums.ldraw.org/thread-{$this->tid}-post-{$this->pid}.html#pid{$this->pid}";
    }
}
