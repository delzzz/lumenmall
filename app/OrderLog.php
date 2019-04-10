<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class OrderLog extends Model{
    protected $table = 'order_log';
    protected $primaryKey = 'log_id';
    //自动维护时间戳
    public $timestamps = false;

}