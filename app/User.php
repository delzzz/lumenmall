<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class User extends Model{
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    //自动维护时间戳
    public $timestamps = false;

    public function getDateFormat()
    {
        return time();
    }
}