<?php
namespace App\Repositories;
use App\Module;
use App\Role;

class DbRoleRepository implements RoleRepositoryInterface{
    //查询所有角色
    public function selectAll()
    {
        return Role::all();
    }
    //新增角色
    public function add(){
        $role = new Role();
    }
}