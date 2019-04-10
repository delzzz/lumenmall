<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class Supplier extends Model{
    protected $table = 'supplier';
    protected $primaryKey = 'supplier_id';
    //自动维护时间戳
    public $timestamps = false;

    public function getDateFormat()
    {
        return time();
    }
}