<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Account extends Model{
    protected $table = 'account';
    protected $primaryKey = 'account_id';
    //自动维护时间戳
    public $timestamps = false;

}