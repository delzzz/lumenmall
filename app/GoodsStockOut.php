<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class GoodsStockOut extends Model{
    protected $table = 'goods_stock_out';
    protected $primaryKey = 'out_id';
    //自动维护时间戳
    public $timestamps = false;

}