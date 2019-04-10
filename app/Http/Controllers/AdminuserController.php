<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminuserController extends Controller
{
    /**
     * 添加/编辑后台用户
     */
    public function addUser(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'user.user_account' => 'required',
                'user.user_name' => 'required',
                'user.role_id' => 'required',
                'user.email' => 'required',
                'user.password' => 'required',
            ]);
            $user = $request->input('user');
            $user_id = $request->input('user.user_id');
            $remark = $request->input('user.remark')??null;
            if (empty($user_id) || $user_id == '') {
                //添加
                $count = \DB::table('admin_user')->where('user_account',$user['user_account'])->count();
                if($count>0){
                    $msg['data'] = '该账号已存在';
                    $msg['success'] = false;

                }
                else{
                    $insertId = \DB::table('admin_user')->insertGetId(
                        ['user_account' => $user['user_account'], 'user_name' => $user['user_name'], 'role_id' => $user['role_id'],
                            'create_time' => date('Y-m-d H:i:s'), 'email' => $user['email'], 'password' => md5($user['password'])]
                    );
                    $msg['data'] = $insertId;
                    $msg['success'] = true;
                }
            } else {
                //编辑
                $updateId = \DB::table('admin_user')->where('user_id', $user_id)->update([
                    'user_account' => $user['user_account'], 'user_name' => $user['user_name'], 'role_id' => $user['role_id'],
                    'email' => $user['email'], 'password' => md5($user['password']), 'remark' => $remark
                ]);
                $msg['data'] = $updateId;
                $msg['success'] = true;
            }
            return $msg;
        } else {
            return -1;
        }
    }

    /**
     * 查询所有用户
     */
    public function selectAll(Request $request)
    {
        $userAccount = $request->input('user_account');
        $userName = $request->input('user_name');
        $roleId = $request->input('role_id');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $totalCount = \DB::table('admin_user')
            ->where(function ($query) use ($userAccount) {
                if ($userAccount != '' || !empty($userAccount)) {
                    $query->where('user_account', 'like', '%' . $userAccount . '%');
                }
            })
            ->where(function ($query) use ($userName) {
                if ($userName != '' || !empty($userName)) {
                    $query->where('user_name', 'like', '%' . $userName . '%');
                }
            })
            ->where(function ($query) use ($roleId) {
                if ($roleId != '' || !empty($roleId)) {
                    $query->where('role_id', $roleId);
                }
            })
            ->where('is_delete', 0)
            ->count();
        $user = \DB::table('admin_user')
            ->where(function ($query) use ($userAccount) {
                if ($userAccount != '' || !empty($userAccount)) {
                    $query->where('user_account', 'like', '%' . $userAccount . '%');
                }
            })
            ->where(function ($query) use ($userName) {
                if ($userName != '' || !empty($userName)) {
                    $query->where('user_name', 'like', '%' . $userName . '%');
                }
            })
            ->where(function ($query) use ($roleId) {
                if ($roleId != '' || !empty($roleId)) {
                    $query->where('role_id', $roleId);
                }
            })
            ->where('is_delete', 0)
            ->when(true, function ($query) use ($page, $perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->offset(($page - 1) * $perPage);
                }
            })
            ->when(true, function ($query) use ($perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->limit($perPage);
                }
            })
            ->get();
        $userList['totalCount'] = $totalCount;
        $userList['itemList'] = $user;
        return json_encode($userList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 查询某个用户
     */
    public function selectOne(Request $request, $userId)
    {
        if ($userId == null || $userId == '') {
            return '缺少user_id';
        }
        $userList = \DB::table('admin_user')->where('user_id', $userId)->get();
        return json_encode($userList, JSON_UNESCAPED_UNICODE);
    }


    //批量删除用户
    public function delete(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, ['user_ids' => 'required']);
            $userIds = $request->input('user_ids');
            $userArr = explode(',', $userIds);
            foreach ($userArr as $key => $value) {
                $id = \DB::table('admin_user')->where('user_id', $value)->update(['is_delete' => 1]);
            }
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
    }

    //登录日志列表
    public function getLoginRecord(Request $request)
    {
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $totalCount = \DB::table('admin_log as log')->count();
        $logInfo = \DB::table('admin_log as log')
            ->join('admin_user as user', 'log.user_id', '=', 'user.user_id')
            ->join('role', 'role.role_id', '=', 'user.role_id')
            ->select('log.create_time', 'user.user_name', 'role.role_name')
            ->when(true, function ($query) use ($page, $perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->offset(($page - 1) * $perPage);
                }
            })
            ->when(true, function ($query) use ($perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->limit($perPage);
                }
            })
            ->get();
        $logList['totalCount'] = $totalCount;
        $logList['itemList'] = $logInfo;
        return json_encode($logList, JSON_UNESCAPED_UNICODE);
    }

}
