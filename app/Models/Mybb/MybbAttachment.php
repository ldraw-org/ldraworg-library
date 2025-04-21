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

    protected function casts(): array
    {
        return [
            'posthash' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(MybbUser::class, 'uid', 'uid');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(MybbPost::class, 'pid', 'pid');
    }

    #[Scope]
    protected function omrFiles(Builder $query)
    {
        $query
            ->where(fn (Builder $qu) => $qu
                ->orWhere('filename', 'LIKE', '%.ldr')
                ->orWhere('filename', 'LIKE', '%.mpd')
            )
            ->whereRelation('post', 'fid', config('ldraw.mybb_omr.omr_forum_id'));
    }

    public function filePath(): string
    {
        return config('ldraw.mybb_omr.attachment_path') . $this->attachname;
    }

    public function get(): string
    {
        return file_get_contents($this->filePath());
    }
}
