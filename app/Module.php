<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Module extends Model{
    protected $table = 'module';
    protected $primaryKey = 'module_id';
    //自动维护时间戳
    public $timestamps = false;

}