<?php

namespace App\Http\Controllers;

use App\ThirdExt\PayCore\wechat\JsApiPay;
use Illuminate\Http\Request;
use App\ThirdExt\PayCore\alipay\alipaySingle;
use App\ThirdExt\PayCore\wechat\WxPaySingle;
use SebastianBergmann\CodeCoverage\Report\Xml\Method;

class PayController extends Controller
{
    //支付宝付款
    public function pay(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required',
        ]);
        //获取付款方式
        $orderId = $request->input('order_id');
        $orderInfo = \DB::table('order')
            ->join('order_goods', 'order.order_id', '=', 'order_goods.order_id')
            ->where('order.order_id', $orderId)
            ->select('pay_method', 'goods_name', 'order_total', 'order_sn')
            ->first();
        $alipay = new alipaySingle();
        $alipay->pay($orderInfo->goods_name, $orderInfo->order_total, $orderInfo->order_sn);
    }

    //微信支付
    public function WeixinPay(Request $request)
    {
        $tools = new JsApiPay();
        $openId = $request->session()->get("openid");
        if (isset($openId)) {
            $orderId = $_POST['order_id'];
            $orderInfo = \DB::table('order')
                ->join('order_goods', 'order.order_id', '=', 'order_goods.order_id')
                ->where('order.order_id', $orderId)
                ->select('pay_method', 'goods_name', 'order_total', 'order_sn')
                ->first();
            //下单
            $input = new \WxPayUnifiedOrder();
            $input->SetBody($orderInfo->goods_name);
            //$input->SetAttach('test');
            //\WxPayConfig::MCHID . date("YmdHis")
            $input->SetOut_trade_no($orderInfo->order_sn);
            $input->SetTotal_fee($orderInfo->order_total * 100);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetNotify_url(\WxPayConfig::NOTIFY_URL);
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            $order = \WxPayApi::unifiedOrder($input);
            $jsApiParameters = $tools->GetJsApiParameters($order);
            return $jsApiParameters;
        } else {
            echo '请在微信客户端打开';
        }
    }


}






