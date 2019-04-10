<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Order extends Model{
    protected $table = 'order';
    protected $primaryKey = 'order_id';
    //自动维护时间戳
    public $timestamps = false;

}