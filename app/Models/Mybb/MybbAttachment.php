<?php

namespace App\Models\Mybb;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class MybbAttachment extends Model
{
    protected $table = 'mybb_attachments';
    protected $primaryKey = 'aid';
    public $timestamps = false;
    protected $connection = 'mybb';

    public function user(): BelongsTo
    {
        return $this->belongsTo(MybbUser::class, 'uid', 'uid');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(MybbPost::class, 'pid', 'pid');
    }
}
