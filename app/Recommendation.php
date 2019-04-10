<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Recommendation extends Model{
    protected $table = 'recommendation';
    protected $primaryKey = 'rid';
    //自动维护时间戳
    public $timestamps = false;

}