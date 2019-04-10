<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Qiniu\Auth;

class QiniuController extends Controller
{
    public function getToken(Request $request){
        require_once(dirname(dirname(dirname(__DIR__))).'/vendor/qiniu/php-sdk/autoload.php');
        $accessKey = 'bdwqwF5SJiIKUeQKt5eDJ_1KKcdiDtrX0BCqV-j0';
            $secretKey = 'fE1sY3EcK81b0fDyQB1YgKgVwRdUZ1lp2uPF3wiP';
            $auth = new Auth($accessKey, $secretKey);
            $bucket = 'csyl';
            // 生成上传Token
            $token = $auth->uploadToken($bucket);
            return json_encode($token, JSON_UNESCAPED_UNICODE);
        }


}