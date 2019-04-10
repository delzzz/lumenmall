<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Role extends Model{
    protected $table = 'role';
    protected $primaryKey = 'role_id';
    //自动维护时间戳
    public $timestamps = false;

}