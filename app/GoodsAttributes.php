<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class GoodsAttributes extends Model{
    protected $table = 'goods_attributes';
    protected $primaryKey = 'attr_id';
    //自动维护时间戳
    public $timestamps = false;

}