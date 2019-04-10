<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class ModuleRole extends Model{
    protected $table = 'module_role';
    protected $primaryKey = 'module_role_id';
    //自动维护时间戳
    public $timestamps = false;

}