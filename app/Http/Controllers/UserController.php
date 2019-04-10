<?php

namespace App\Http\Controllers;

use App\DBRegion;
use App\DBUser;
use App\Verifycode;
use Illuminate\Http\Request;

class UserController extends Controller
{

    /**
     * 注册
     */
    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'mobile' => 'required',
                'password' => 'required',
                'verify_code' => 'required',
                'msg_code'=>'required'
            ]);
            $mobile = $request->input('mobile');
            $password = $request->input('password');
            $verifyCode = $request->input('verify_code');
            $msgCode = $request->input('msg_code');
            $vfy = $request->session()->get('verifyCode');
            //判断短信验证码

            //判断验证码
//            if ($vfy != $verifyCode) {
//                $msg['success'] = false;
//                $msg['data'] = '验证码不正确';
//            }
            //判断用户是否已经存在
            $count = \DB::table('user')->where('mobile',$mobile)->count();
            if($count>0){
                $msg['success'] = false;
                $msg['data'] = '用户已存在';
            }
            else{
                $insertId = \DB::table('user')->insertGetId([
                    'mobile'=>$mobile,
                    'password' => md5($password),
                    'create_time' => date('Y-m-d H:i:s')
                ]);
                $msg['data'] = $insertId;
                $msg['success'] = true;
            }
            return $msg;
        }
    }

    /**
     * @param Request $request
     * 忘记密码
     */
    public function forgetPassword(Request $request){
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'mobile' => 'required',
                'password' => 'required',
                'verify_code' => 'required',
                'msg_code'=>'required'
            ]);
            $mobile = $request->input('mobile');
            $password = $request->input('password');
            $verifyCode = $request->input('verify_code');
            $msgCode = $request->input('msg_code');
            $vfy = $request->session()->get('verifyCode');
            //判断短信验证码

            //判断验证码
//            if ($vfy != $verifyCode) {
//                $msg['success'] = false;
//                $msg['data'] = '验证码不正确';
//            }
            //判断用户是否存在
            $count = \DB::table('user')->where('mobile',$mobile)->count();
            if($count>0){
                $updateId = \DB::table('user')->where('mobile',$mobile)->update([
                    'password' => md5($password),
                ]);
                $msg['data'] = $updateId;
                $msg['success'] = true;
            }
            else{
                $msg['success'] = false;
                $msg['data'] = '用户不存在';
            }
            return $msg;
        }
    }

    /**
     * @param Request $request
     * 发送验证码
     */
    public function sendMessage(Request $request){

    }

    /**
     * @param Request $request
     * 用户列表
     */
    public function selectAll(Request $request)
    {
        if ($request->isMethod('post')) {
            $account = $request->input('account');
            $nickname = $request->input('nickname');
            $page = $request->input('page');
            $perPage = $request->input('perPage');
            $totalCount = \DB::table('user')
                ->where(function ($query) use ($account, $nickname) {
                    if ($account !== '' || !empty($account)) {
                        $query->where('mobile', 'like', '%' . $account . '%');
                    }
                    if ($nickname !== '' || !empty($nickname)) {
                        $query->where('nickname', 'like', '%' . $nickname . '%');
                    }
                })
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
                ->count();
            $userInfo = \DB::table('user')
                ->where(function ($query) use ($account, $nickname) {
                    if ($account !== '' || !empty($account)) {
                        $query->where('mobile', 'like', '%' . $account . '%');
                    }
                    if ($nickname !== '' || !empty($nickname)) {
                        $query->where('nickname', 'like', '%' . $nickname . '%');
                    }
                })
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
                ->select('user_id', 'nickname', 'gender', 'birthday', 'mobile', 'avatar', 'create_time', 'user_source', 'status')
                ->get();
            foreach ($userInfo as $key => $value) {
                //消费金额
                $consume = \DB::table('order')->select('order_total')
                    ->where('user_id', $value->user_id)
                    ->whereIn('order_status', [0, 3])
                    ->where('pay_status', 1)->sum('order_total');
                $value->consume = $consume;
            }
            $userList['totalCount'] = $totalCount;
            $userList['itemList'] = $userInfo;
            return json_encode($userList, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param Request $request
     * 查询用户详情
     */
    public function selectOne(Request $request, $userId)
    {
        if ($userId == '' || empty($userId)) {
            return '缺少user_id';
        }
        $userDetail = \DB::table('user')
            ->where('user_id', $userId)
            ->first();
        //消费金额
        $consume = \DB::table('order')->select('order_total')
            ->where('user_id', $userId)
            ->whereIn('order_status', [0, 3])
            ->where('pay_status', 1)->sum('order_total');
        $userDetail->consume = $consume;
        //居住地
        $modelRegion = new DBRegion();
       // return $userDetail->province;
        if($userDetail->province !==''){
            $livingPlace = $modelRegion->getFullRegionName($userDetail->province, $userDetail->city, $userDetail->county);
        }
        $userDetail->living_place = $livingPlace;
        //收货地址
        $modelUser = new DBUser();
        $addressList = $modelUser->getAddressList($userId);
        foreach ($addressList as $key => $value) {
            $value->consignee_address = $modelRegion->getFullRegionName($value->province, $value->city, $value->county) . $value->address;
        }
        $userDetail->addressList = $addressList;
        return json_encode($userDetail, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * 用户启用禁用
     */
    public function setUserStatus(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'user_id' => 'required',
                'status' => 'required'
            ]);
            $userId = $request->input('user_id');
            $status = $request->input('status');
            $userArr = explode(',', $userId);
            foreach ($userArr as $key => $value) {
                $id = \DB::table('user')->where('user_id', $value)->update(['status' => $status]);
            }
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
    }

    /**
     * @param Request $request
     * @param $userId
     * 用户地址列表
     */
    public function addressList(Request $request, $userId)
    {
        $modelUser = new DBUser();
        $modelRegion = new DBRegion();
        $addressList = $modelUser->getAddressList($userId);
        foreach ($addressList as $key => $value) {
            $value->consignee_address = $modelRegion->getFullRegionName($value->province, $value->city, $value->county) . $value->address;
        }
        return json_encode($addressList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * @param $userId
     * 用户地址详情
     */
    public function addressDetail(Request $request, $addressId)
    {
        $addressDetail = \DB::table('user_address')->where('address_id', $addressId)->first();
        return json_encode($addressDetail, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 添加收货地址
     */
    public function addAddress(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'user_id' => 'required',
                'province' => 'required',
                'city' => 'required',
                'county' => 'required',
                'zipcode' => 'required',
                'address' => 'required',
                'consignee' => 'required',
                'mobile' => 'required'
            ]);
            $addressId = $request->input('address_id');
            $userId = $request->input('user_id');
            $province = $request->input('province');
            $city = $request->input('city');
            $county = $request->input('county');
            $zipcode = $request->input('zipcode');
            $address = $request->input('address');
            $consignee = $request->input('consignee');
            $mobile = $request->input('mobile');
            if ($addressId !== '' && !empty($addressId)) {
                //修改
                $id = \DB::table('user_address')->where('address_id', $addressId)->update([
                    'user_id' => $userId,
                    'province' => $province,
                    'city' => $city,
                    'county' => $county,
                    'zipcode' => $zipcode,
                    'address' => $address,
                    'consignee' => $consignee,
                    'mobile' => $mobile
                ]);
            } else {
                //添加
                $id = \DB::table('user_address')->insertGetId([
                    'user_id' => $userId,
                    'province' => $province,
                    'city' => $city,
                    'county' => $county,
                    'zipcode' => $zipcode,
                    'address' => $address,
                    'consignee' => $consignee,
                    'mobile' => $mobile
                ]);
            }
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
    }

    /**
     * @param Request $request
     * @param $addressId
     * 设置默认地址
     */
    public function setDefaultAddress(Request $request)
    {
        $userId = $request->input('user_id');
        $addressId = $request->input('address_id');
        $flag = $request->input('flag');
        //设置默认
        if ($flag == 1) {
            \DB::table('user_address')
                ->where('user_id', $userId)
                ->update([
                    'is_default' => 0
                ]);
            $id = \DB::table('user_address')
                ->where('address_id', $addressId)
                ->update([
                    'is_default' => 1
                ]);
        } else {
            //flag为0取消默认
            $id = \DB::table('user_address')
                ->where('address_id', $addressId)
                ->update([
                    'is_default' => 0
                ]);
        }
        $msg['success'] = true;
        $msg['data'] = $id;
        return $msg;
    }

    //删除地址
    public function deleteAddress(Request $request, $addressId)
    {
        if ($addressId == '' || empty($addressId)) {
            $msg['success'] = false;
            $msg['data'] = '缺少address_id';
        }
        else{
            $id = \DB::table('user_address')
                ->where('address_id', $addressId)
                ->update([
                    'is_delete' => 1
                ]);
            $msg['success'] = true;
            $msg['data'] = $id;
        }
        return $msg;
    }

    /**
     * @param Request $request
     * @param $userId
     * 用户头像昵称手机号
     */
    public function getUserBasic(Request $request,$userId){
        $info = \DB::table('user')->where('user_id',$userId)->select('mobile','nickname','avatar')->first();
        return json_encode($info, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * @return mixed
     * 用户更改头像
     */
    public function changeAvatar(Request $request){
        if ($request->isMethod('post')) {
            $userId = $request->input('user_id');
            $avatar = $request->input('avatar');
            $this->validate($request, [
                'user_id' => 'required',
                'avatar' => 'required'
            ]);
            $id = \DB::table('user')->where('user_id',$userId)->update(['avatar'=>$avatar]);
            $msg['success'] = true;
            $msg['data'] = $id;
            $msg['avatar'] = $avatar;
            return $msg;
        }
    }

    /**
     * @param Request $request
     * @param $userId
     * 用户个人资料
     */
    public function getUserInfo(Request $request,$userId){
        $info = \DB::table('user')->where('user_id',$userId)->select('mobile','nickname','gender','birthday','province','city','county')->first();
        return json_encode($info, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * @param $userId
     * 修改用户个人资料
     */
    public function editUserInfo(Request $request){
        $userId = $request->input('user_id');
        $nickname = $request->input('nickname');
        $gender = $request->input('gender');
        $birthday = $request->input('birthday');
        $province = $request->input('province');
        $city = $request->input('city');
        $county = $request->input('county');
        if(!empty($nickname)){
            $id = \DB::table('user')->where('user_id',$userId)->update([
                'nickname'=>$nickname
            ]);
        }
        if(!empty($gender)){
            $id = \DB::table('user')->where('user_id',$userId)->update([
                'gender'=>$gender
            ]);
        }
        if(!empty($birthday)){
            $id = \DB::table('user')->where('user_id',$userId)->update([
                'birthday'=>$birthday
            ]);
        }
        if(!empty($province) && $city !=='' && $county !==''){
            $id = \DB::table('user')->where('user_id',$userId)->update([
                'province'=>$province,
                'city'=>$city,
                'county'=>$county
            ]);

        }
        $msg['success'] = true;
        $msg['data'] = $id;
        return $msg;
    }

    /**
     * @param Request $request
     * 修改密码
     */
    public function editPassword(Request $request){
        $userId = $request->input('user_id');
        $password = $request->input('password');
        $newPassword = $request->input('new_password');
        $this->validate($request, [
            'user_id' => 'required',
            'password' => 'required',
            'new_password' => 'required'
        ]);
        $userModel = new DBUser();
        if(!$userModel->checkPassword($userId,$password)){
            $msg['data'] = '密码错误';
            $msg['success'] = false;
        }
        else{
            $id = \DB::table('user')->where('user_id',$userId)->update(['password'=>md5($newPassword)]);
            $msg['data'] = $id;
            $msg['success'] = true;
        }
        return $msg;
    }

    //点赞取消赞商品
    public function followGoods(Request $request){
        $spuId = $request->input('spu_id');
        $userId = 1;
        $count = \DB::table('user_goods')->where(['spu_id'=>$spuId,'user_id'=>$userId])->count();
        if($count>0){
            \DB::table('user_goods')->where(['spu_id'=>$spuId,'user_id'=>$userId])->delete();
        }
        else{
            \DB::table('user_goods')->insertGetId([
                'spu_id'=>$spuId,
                'user_id' => $userId,
            ]);
        }
        $followNum = \DB::table('user_goods')->where('spu_id',$spuId)->count();
        $msg['num'] = $followNum;
        $msg['success'] = true;
        return $msg;
    }
}
