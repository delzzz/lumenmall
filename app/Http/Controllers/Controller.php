<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    protected $userAgent;
    protected $userId;
    protected $userName;
    public function __construct(Request $request)
    {
        header( "Access-Control-Allow-Origin: *" );
        header( "Access-Control-Allow-Methods:POST,GET,PUT,PATCH,DELETE" );
        header('Access-Control-Allow-Headers:x-requested-with,content-type,token');
        $token =$request->header('token');
        if($token!=='' && $token !== null){
            //有token的请求
            $tokenStr = base64_decode($token);
            $tokenArr = explode('|',$tokenStr);
            $userArr = json_decode($tokenArr[1]);
            if($tokenArr[0] == $request->header('user_agent')){
                $this->userAgent = $tokenArr[0];
            }
            else{
                $msg['success'] = false;
                $msg['data'] = '浏览器不一致';
                return $msg;
            }
            if(!empty($userArr->user_id)){
                $this->userId = $userArr->user_id;
                $this->userName = $userArr->user_name;
            }
            else{
                $msg['success'] = false;
                $msg['data'] = '用户没有登录';
                return $msg;
            }
            if($tokenArr[2] != env('APP_KEY')){
                $msg['success'] = false;
                $msg['data'] = 'APP_KEY不一致';
                return $msg;
            }
        }


    }
}
