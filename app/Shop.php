<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Shop extends Model{
    protected $table = 'shop';
    protected $primaryKey = 'shop_id';
    //自动维护时间戳
    public $timestamps = false;

}