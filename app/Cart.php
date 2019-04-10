<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Cart extends Model{
    protected $table = 'cart';
    protected $primaryKey = 'cart_id';
    //自动维护时间戳
    public $timestamps = false;

}