<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Promotion extends Model{
    protected $table = 'promotion';
    protected $primaryKey = 'promotion_id';
    //自动维护时间戳
    public $timestamps = false;

}