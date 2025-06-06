<?php

namespace App\Models\Part;

use App\Models\Traits\HasPart;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPartHistory
 */
class PartHistory extends Model
{
    use HasPart;
    use HasUser;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_at',
        'comment',
        'part_id',
    ];

    protected $with = ['user'];

    public function toString(): string
    {
        $date = date_format(date_create($this->created_at), "Y-m-d");
        $user = $this->user->historyString();
        return "0 !HISTORY {$date} {$user} {$this->comment}";
    }
}
