<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class GoodsSpec extends Model{
    protected $table = 'goods_spec';
    protected $primaryKey = 'goods_spec_id';
    //自动维护时间戳
    public $timestamps = false;

}