<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class Goods extends Model{
    protected $table = 'goods';
    protected $primaryKey = 'goods_id';
    //自动维护时间戳
    public $timestamps = false;

}