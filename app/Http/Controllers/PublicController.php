<?php

namespace App\Http\Controllers;

use App\DBGoods;
use Illuminate\Http\Request;
use App\DBUser;
use App\Verifycode;
use App\DBPromotion;

class PublicController extends Controller
{
//    public function __construct(Request $request)
//    {
//        header( "Access-Control-Allow-Origin: *" );
//        header( "Access-Control-Allow-Methods:POST,GET,PUT,PATCH,DELETE" );
//
//    }
    /**
     * 管理员登录
     */
    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'user_account' => 'required',
                'password' => 'required',
                'verify_code' => 'required'
            ]);
            $userAccount = $request->input('user_account');
            $password = $request->input('password');
            $verifyCode = $request->input('verify_code');
            $vfy = $request->session()->get('verifyCode');
            //判断验证码
//            if ($vfy != $verifyCode) {
//                $msg['data'] = '验证码不正确';
//                $msg['success'] = false;
//            } else {
            $count = \DB::table('admin_user')->where('user_account', $userAccount)->count();
            if ($count == 0) {
                $msg['data'] = '账号不存在';
                $msg['success'] = false;
            } else {
                $userInfo = \DB::table('admin_user')->where(['user_account' => $userAccount, 'password' => md5($password)])->first();
                if (empty($userInfo) || $userInfo == null) {
                    $msg['data'] = '密码错误';
                    $msg['success'] = false;
                } else {
                    //$request->session()->put('adminId', $userInfo->user_id);
                    //$request->session()->put('adminName', $userInfo->user_name);
                    \DB::table('admin_user')->where('user_id', $userInfo->user_id)
                        ->update(['last_ip' => $_SERVER["REMOTE_ADDR"], 'last_login' => date('Y-m-d H:i:s')]);
                    \DB::table('admin_log')->insertGetId([
                        'user_id' => $userInfo->user_id,
                        'create_time' => date('Y-m-d H:i:s'),
                        'login_ip' => $_SERVER["REMOTE_ADDR"]
                    ]);
                    $userAgent = $request->header('user_agent');
                    $tokenStr = $userAgent . '|' . json_encode(array('user_id' => $userInfo->user_id, 'user_name' => $userInfo->user_name)) . '|' . env('APP_KEY');
                    $token = base64_encode($tokenStr);
                    $msg['data'] = $userInfo->user_id;
                    $msg['success'] = true;
                    $msg['token'] = $token;
                }
            }
            //  }
            return $msg;
        }
    }

    /**
     * @param Request $request 获取验证码
     */
    public function getVerifyCode(Request $request)
    {
        $verifyModel = new Verifycode();
        $chnr = $verifyModel->getVerifycode(4, 4);
        $sign = $request->input('\sign');
        $this->setVerifyCode($sign, $chnr);
        return $verifyModel->checkVerifycode($chnr);
    }

    public function setVerifyCode($sign, $chnr)
    {
        $myfile = fopen("login.txt", "a");
        $arr[$sign] = $chnr;
        fwrite($myfile, json_encode($arr) . ',');
    }

    //用户登录次数
    public function userLoginCount(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'mobile' => 'required',
            ]);
            $mobile = $request->input('mobile');
            $userModel = new DBUser();
            $count = $userModel->getUserLoginCount($mobile);
            return $count;
        }
    }

    /**
     * @param Request $request
     * 用户登录
     */
    public function userLogin(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'mobile' => 'required',
                'password' => 'required',
            ]);
            $mobile = $request->input('mobile');
            $password = $request->input('password');
            $sign = $request->input('sign');
            $file = "login.txt";
            $fh = fopen($file, "rt");
         //   return filesize($file);
            if(filesize($file)>0){
                $userdata = fread($fh, filesize($file));
                fclose($fh);
                $dataString = substr($userdata, 0, strlen($userdata) - 1);
                $dataArr = explode(',', $dataString);
                $vfy = '';
                foreach ($dataArr as $key => $value) {
                    $valueArr = json_decode($value);
                    foreach ($valueArr as $k => $v) {
                        if ($k == $sign) {
                            $vfy = $v;
                        }
                    }
                }
            }
            $verifyCode = $request->input('verify_code');
            //判断验证码
            $userModel = new DBUser();
            $count = $userModel->getUserLoginCount($mobile);
            if ($count > 3) {
                if ($verifyCode == '') {
                    $msg['data'] = '验证码为空';
                    $msg['success'] = false;
                    return $msg;
                }
                if (strtolower($vfy) !== strtolower($verifyCode)) {
                    $msg['data'] = '验证码不正确';
                    $msg['success'] = false;
                    return $msg;
                }
            }
            $userInfo = \DB::table('user')->where(['mobile' => $mobile, 'password' => md5($password)])->first();
            if ($userInfo != null && !empty($userInfo)) {
                //$request->session()->put('userId', $userInfo->user_id);
                //$request->session()->put('nickname', $userInfo->nickname);
                \DB::table('user_log')->insert([
                    'mobile' => $mobile,
                    'ip_addr' => $_SERVER["REMOTE_ADDR"],
                    'last_login' => date('Y-m-d H:i:s'),
                    'status' => 1
                ]);
                $userAgent = $request->header('user_agent');
                $tokenStr = $userAgent . '|' . json_encode(array('user_id' => $userInfo->user_id, 'user_name' => $mobile)) . '|' . env('APP_KEY');
                $token = base64_encode($tokenStr);
                $msg['user_id'] = $userInfo->user_id;
                $msg['user_name'] = $mobile;
                $msg['nickname'] = $userInfo->nickname;
                $msg['avatar'] = $userInfo->avatar;
                $msg['success'] = true;
                $msg['token'] = $token;
            } else {
                \DB::table('user_log')->insert([
                    'mobile' => $mobile,
                    'ip_addr' => $_SERVER["REMOTE_ADDR"],
                    'last_login' => date('Y-m-d H:i:s'),
                    'status' => 0
                ]);
                $msg['data'] = '账号或密码错误';
                $msg['success'] = false;
            }
        }
        return $msg;

    }

    /**
     * @param Request $request
     * 用户退出登录
     */
    public function userLogout(Request $request)
    {
        $request->session()->forget('userId');
        $msg['success'] = true;
        return $msg;
    }

    /**
     * @param Request $request
     * 管理员退出登录
     */
    public function adminLogout(Request $request)
    {
        $request->session()->forget('adminId');
        $msg['success'] = true;
        return $msg;
    }

    /**
     * @param Request $request
     * 检测用户是否登录
     */
//    public function isLogin(Request $request)
//    {
//        $userId = $request->session()->get('userId');
//        if (!empty($userId)) {
//            $msg['data'] = $userId;
//            $msg['success'] = true;
//        } else {
//            $msg['data'] = 0;
//            $msg['success'] = false;
//        }
//        return $msg;
//    }

    /**
     * @param Request $request
     * 检测管理员是否登录
     */
//    public function isAdminLogin(Request $request)
//    {
//        $adminId = $request->session()->get('adminId');
//        if (!empty($adminId)) {
//            $msg['data'] = $adminId;
//            $msg['success'] = true;
//        } else {
//            $msg['data'] = 0;
//            $msg['success'] = false;
//        }
//        return $msg;
//    }
//    public function checkVerifycode(Request $request){
//        $vfy =  $request->session()->get('verifyCode');
//       // var_dump($vfy);
//    }

    /**
     * 7天自动确认收货 3天自动关闭订单 过了开售结束时间自动下架 关闭过期优惠
     */
    public function autoConfirm(Request $request)
    {
        //7天后自动确认收货
        $userModel = new DBUser();
        $userModel->confirmCollection();
        //3天自动关闭订单
        $userModel->closeOrder();
        //过了开售结束时间自动下架
        $goodsModel = new DBGoods();
        $goodsModel->turnOffGoods();
        //关闭过期优惠
        $promotionModel = new DBPromotion();
        $promotionModel->turnOffPromotion();
        //关闭过期推荐位
        $promotionModel->turnOffRecommend();
    }

}