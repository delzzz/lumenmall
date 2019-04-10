<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class SpecInfo extends Model{
    protected $table = 'spec_info';
    protected $primaryKey = 'spec_info_id';
    //自动维护时间戳
    public $timestamps = false;

}