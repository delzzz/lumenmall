<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ThirdExt\PayCore\wechat\WxPayNotify;
use App\ThirdExt\PayCore\wechat\JsApiPay;

class WechatpayController extends Controller
{
    //异步回调
    function notifyPage()
    {
        $notify = new WxPayNotify();
        $notify->callBack();
        $res = $notify->GetValues();
        $xml = file_get_contents('php://input');
        $getData = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $myfile = fopen("test.txt", "a");
        fwrite($myfile, json_encode($getData));
        if (!empty($res['return_code']) && $res['return_code'] == 'SUCCESS') {
            //交易明细记录
            $orderId = \DB::table('order')->where(['order_sn' => $getData['out_trade_no']])->value('order_id');
            //操作付款状态
            \DB::table('order')->where('order_id', $orderId)->update([
                'pay_status' => 1,
                'pay_time' => date('Y-m-d H:i:s')
            ]);
            \DB::table('trade_record')->insertGetId([
                'order_id' => $orderId,
                'money' => $getData['total_fee']/100,
                'type' => 1, //0支出 1收入
                'create_time' => date('Y-m-d H:i:s')
            ]);
            \DB::table('wechat_trade')->insertGetId([
                'order_sn' => $getData['out_trade_no'],
                'transaction_id' => $getData['transaction_id'],
                'result_code' => $getData['result_code'],
                'openid' => $getData['openid'],
                'trade_type' => $getData['trade_type'],
                'total_fee' => $getData['total_fee']/100,
                'create_time' => date('Y-m-d H:i:s')
            ]);
        }
        return $notify->ToXml();//返回给微信确认
    }

    //获取openid
    function getOpenId(Request $request){
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();
        $request->session()->put('openid',$openId);
        $backUrl = $request->session()->get('backurl');
        return redirect($backUrl);
    }

    //页面
    function index(Request $request){
        return view('wechat.index');
    }
}






