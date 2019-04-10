<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Category extends Model{
    protected $table = 'category';
    protected $primaryKey = 'category_id';
    //自动维护时间戳
    public $timestamps = false;

}