<?php

namespace App\Http\Controllers;

use App\Repositories\ModuleRepositoryInterface;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * @return string
     * 角色列表
     */
    public function selectAll()
    {
        $moduleList = \DB::table('role')->select('role_id','role_name','description','create_time','status')->get();
        return json_encode($moduleList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取某角色信息
     */
    public function selectOne(Request $request,$roleId){
        if($roleId == null || $roleId == ''){
            return '缺少role_id';
        }
        $moduleList = \DB::table('role')->where('role_id',$roleId)->select('role_id','role_name','description','create_time','status')->get();
        return json_encode($moduleList, JSON_UNESCAPED_UNICODE);
    }

    //修改角色信息
    public function editRole(Request $request){
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'role.role_id' => 'required',
                'role.role_name' => 'required',
            ]);
            $roleId = $request->input('role.role_id');
            $roleName = $request->input('role.role_name');
            $description = $request->input('role.description');
            $id = \DB::table('role')->where('role_id', $roleId)->update(
                ['role_name' => $roleName, 'description' => $description, 'update_time' => date('Y-m-d H:i:s')]
            );
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
    }

    /**
     * 添加角色
     */
    public function add(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'role.role_name' => 'required',
            ]);
            $roleName = $request->input('role.role_name');
            $description = $request->input('role.description');
            $id = \DB::table('role')->insertGetId(
                ['role_name' => $roleName, 'description' => $description, 'create_time' => date('Y-m-d H:i:s')]
            );
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
    }

    /**
     * 获取某角色所有权限
     */
    public function getAccess(Request $request,$roleId)
    {
        //return $roleId;
        if($roleId == null || $roleId == ''){
            return '缺少role_id';
        }
        $accessList = \DB::table('module_role')->where('role_id', $roleId)->pluck('module_id');
        return json_encode($accessList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 修改某角色权限
     */
    public function editAccess(Request $request)
    {
        $this->validate($request, [
            'role.role_id' => 'required',
        ]);
        $roleId = $request->input('role.role_id');
        $moduleId = $request->input('role.module_id');
        $moduleArr = explode(',', $moduleId);
        \DB::table('module_role')->where('role_id', $roleId)->delete();
        foreach ($moduleArr as $key => $value) {
            $insertId = \DB::table('module_role')->insertGetId(
                ['role_id' => $roleId, 'module_id' => $value, 'create_time' => date('Y-m-d H:i:s')]
            );
        }
        $msg['success'] = true;
        $msg['data'] = $insertId;
        return $msg;
    }

    //改变角色状态
//    public function changeRoleStatus(Request $request){
//        $this->validate($request, [
//            'role_id' => 'required',
//            'status'=>'required',
//        ]);
//        $role_id = $request->input('role_id');
//        $status = $request->input('status');
//        $id = \DB::table('role')->where('role_id',$role_id)->update(['status'=>$status]);
//        return json_encode($id, JSON_UNESCAPED_UNICODE);
//    }
}