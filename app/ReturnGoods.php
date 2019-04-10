<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class ReturnGoods extends Model{
    protected $table = 'return_goods';
    protected $primaryKey = 'return_id';
    //自动维护时间戳
    public $timestamps = false;

}