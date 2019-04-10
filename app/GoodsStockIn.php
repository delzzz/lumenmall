<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class GoodsStockIn extends Model{
    protected $table = 'goods_stock_in';
    protected $primaryKey = 'in_id';
    //自动维护时间戳
    public $timestamps = false;

}