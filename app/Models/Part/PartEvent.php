<?php

namespace App\Models\Part;

use App\Models\Traits\HasPart;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasPartRelease;
use App\Models\Traits\HasUser;
use App\Models\VoteType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartEvent extends Model
{
    use HasPartRelease;
    use HasUser;
    use HasPart;

    protected $with = [
        'part_event_type',
        'vote_type'
    ];

    protected $fillable = [
        'created_at',
        'initial_submit',
        'part_id',
        'user_id',
        'vote_type_code',
        'part_release_id',
        'part_event_type_id',
        'comment',
        'deleted_filename',
        'deleted_description',
        'moved_from_filename',
        'moved_to_filename',
        'initial_submit',
        'header_changes',
    ];

    protected function casts(): array
    {
        return  [
            'initial_submit' => 'boolean',
            'header_changes' => 'array',
        ];
    }

    public function part_event_type(): BelongsTo
    {
        return $this->belongsTo(PartEventType::class);
    }

    public function vote_type(): BelongsTo
    {
        return $this->belongsTo(VoteType::class);
    }

    public function processedComment(): ?string
    {
        if (is_null($this->comment)) {
            return null;
        }

        $urlpattern = '#https?:\/\/(?:www\.)?[a-zA-Z0-9@:%._\+~\#=-]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[a-zA-Z0-9()@:%_\+.~\#?&\/=-]*)#u';
        $comment = preg_replace('#\R#us', "\n", $this->comment);
        $comment = preg_replace('#\n{3,}#us', "\n\n", $comment);
        $comment = preg_replace($urlpattern, '<a href="$0">$0</a>', $comment);

        return nl2br($comment);
    }
}
