<?php

namespace App\Models\Mybb;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

class MybbUser extends Model
{
    protected $table = 'mybb_users';
    protected $primaryKey = 'uid';
    public $timestamps = false;
    protected $connection = 'mybb';

    protected $hidden = [
        'password',
        'salt',
        'loginkey',
    ];

    public function library_user(): HasOne
    {
        return $this->setConnection('mysql')->hasOne(User::class, 'forum_user_id', 'uid');
    }

    public static function findFromCookie(?Request $request = null): ?self
    {
        if (is_null($request)) {
            $request = request();
        }
        $mybb = $request->cookies->get('mybbuser', '');
        if ($mybb == '') {
            return null;
        }
        $mybb = explode("_", $mybb);
        // The cookie should be in the format <uid>_<loginkey>
        if (count($mybb) !== 2 || !is_numeric($mybb[0])) {
            return null;
        }
        // Look up the mybb user in the database ad check if in LDraw Member Group
        return self::where('uid', $mybb[0])->where('loginkey', $mybb[1])->first();
    }

    public function inGroup(int $gid): bool
    {
        if ($this->usergroup === $gid) {
            return true;
        }
        if ($this->additional_groups === '') {
            return false;
        }
        $mybb_groups = explode(',', $this->additionalgroups);
        if (in_array($gid, $mybb_groups)) {
            return true;
        }
        return false;
    }

    public function addGroup(int $gid): void
    {
        if ($this->additional_groups === '') {
            $groups = [];
        } else {
            $groups = explode(',', $this->additionalgroups);
        }

        if ($this->usergroup != $gid && !in_array($gid, $groups)) {
            $groups[] = $gid;
            $this->additionalgroups = implode(',', $groups);
        }
    }
}
