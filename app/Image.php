<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Image extends Model{
    protected $table = 'image';
    protected $primaryKey = 'image_id';
    //自动维护时间戳
    public $timestamps = false;

}