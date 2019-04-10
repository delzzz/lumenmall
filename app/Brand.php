<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Brand extends Model{
    protected $table = 'brand';
    protected $primaryKey = 'brand_id';
    //自动维护时间戳
    public $timestamps = false;

}