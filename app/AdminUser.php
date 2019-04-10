<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class AdminUser extends Model{
    protected $table = 'admin_user';
    protected $primaryKey = 'user_id';
    //自动维护时间戳
    public $timestamps = false;

}