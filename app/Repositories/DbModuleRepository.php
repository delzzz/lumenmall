<?php
namespace App\Repositories;
use App\Module;

class DbModuleRepository implements ModuleRepositoryInterface{
    //查询所有模块
    public function selectAll()
    {
        return Module::all();
    }
}