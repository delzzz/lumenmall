<?php
namespace App\ThirdExt\PayCore\wechat;
require_once "WxPay.Api.php";

class WxPaySingle
{
    function setOpenId()
    {
        //①、获取用户openid
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();
    }

}