<?php

namespace App\Models\Part;

use App\Enums\EventType;
use App\Enums\VoteType;
use App\Models\Traits\HasPart;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasPartRelease;
use App\Models\Traits\HasUser;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use App\Collections\PartEventCollection;

#[CollectedBy(PartEventCollection::class)]
class PartEvent extends Model
{
    use HasPartRelease;
    use HasUser;
    use HasPart;

    protected $guarded = [];

    /**
    * @return array{
    *     'initial_submit': 'boolean',
    *     'header_changes': 'array',
    *     'vote_type': 'App\\Enums\\VoteType',
    *     'event_type': 'App\\Enums\\EventType',
    * }
    */
    protected function casts(): array
    {
        return  [
            'initial_submit' => 'boolean',
            'header_changes' => 'array',
            'vote_type' => VoteType::class,
            'event_type' => EventType::class,
        ];
    }

    public function processedComment(): ?HtmlString
    {
        if (is_null($this->comment)) {
            return null;
        }

        $urlpattern = '#https?:\/\/(?:www\.)?[a-zA-Z0-9@:%._\+~\#=-]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[a-zA-Z0-9()@:%_\+.~\#?&\/=-]*)#u';
        $comment = preg_replace('#\R#us', "\n", $this->comment);
        $comment = preg_replace('#\n{3,}#us', "\n\n", $comment);
        $comment = preg_replace($urlpattern, '<a class="underline decoration-dotted hover:decoration-solid" href="$0">$0</a>', $comment);
        $comment = nl2br($comment);

        return new HtmlString($comment);
    }
}
