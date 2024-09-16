<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $username
 * @property string $email
 * @property string $loginname
 * @property string $additionalgroups
 */
class MybbUser extends Model
{
    protected $table = 'mybb_users';
    protected $primaryKey = 'uid';
    public $timestamps = false;
    protected $connection = 'mybb';
}
