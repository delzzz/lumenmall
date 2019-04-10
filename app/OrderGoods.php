<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class OrderGoods extends Model{
    protected $table = 'order_goods';
    protected $primaryKey = 'order_goods_id';
    //自动维护时间戳
    public $timestamps = false;

}