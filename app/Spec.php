<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Spec extends Model{
    protected $table = 'spec';
    protected $primaryKey = 'spec_id';
    //自动维护时间戳
    public $timestamps = false;

}