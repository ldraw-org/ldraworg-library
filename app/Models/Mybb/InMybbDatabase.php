<?php

namespace App\Models\Mybb;

trait InMybbDatabase
{
    public $timestamps = false;
    protected $connection = 'mybb';
}
