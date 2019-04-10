<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class UserAddress extends Model{
    protected $table = 'user_address';
    protected $primaryKey = 'address_id';
    //自动维护时间戳
    public $timestamps = false;

    public function getDateFormat()
    {
        return time();
    }
}