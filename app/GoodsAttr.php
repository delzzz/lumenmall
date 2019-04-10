<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class GoodsAttr extends Model{
    protected $table = 'goods_attr';
    protected $primaryKey = 'goods_attr_id';
    //自动维护时间戳
    public $timestamps = false;

}