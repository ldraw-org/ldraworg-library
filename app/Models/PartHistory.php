<?php

namespace App\Models;

use App\Models\Traits\HasPart;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;

class PartHistory extends Model
{
    use HasPart;
    use HasUser;

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
