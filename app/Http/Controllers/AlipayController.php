<?php

namespace App\Http\Controllers;
use App\ThirdExt\PayCore\alipay\AlipayNotify;
class AlipayController extends Controller{
    //异步回调通知
    function notifyPage()
    {
        require_once(dirname(dirname(__DIR__)).'/ThirdExt/PayCore/alipay/alipay_config.php');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        //$myfile = fopen("test.txt", "a");
        //fwrite($myfile, json_encode($_POST));
        if ($verify_result) {//验证成功
            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            $trade_status = $_POST['trade_status'];
            $buyer_email = $_POST['buyer_email'];
           // if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
           // } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
           // }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            //交易明细记录
            $orderId = \DB::table('order')->where(['order_sn'=>$out_trade_no])->value('order_id');
            //操作付款状态
            \DB::table('order')->where('order_id', $orderId)->update([
                'pay_status' => 1,
                'pay_time' => date('Y-m-d H:i:s')
            ]);
            \DB::table('trade_record')->insertGetId([
                'order_id' => $orderId,
                'money' => $_POST['total_fee'],
                'type' => 1, //0支出 1收入
                'create_time' => date('Y-m-d H:i:s')
            ]);
            //记录支付宝流水表
            \DB::table('alipay_trade')->insertGetId([
                'order_sn' => $out_trade_no,
                'trade_no' => $trade_no,
                'trade_status' => $_POST['trade_status'],
                'total_fee' =>  $_POST['total_fee'],
                'buyer_email' => $_POST['buyer_email'],
                'create_time' => date('Y-m-d H:i:s')
            ]);

            echo "success";        //请不要修改或删除

        } else {
            //验证失败
            echo "fail";
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    //同步回调通知
    function returnPage()
    {
        require_once(dirname(dirname(__DIR__)).'/ThirdExt/PayCore/alipay/alipay_config.php');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        if($verify_result) {//验证成功
            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];
            //支付宝交易号
            $trade_no = $_GET['trade_no'];
            //交易状态
            $trade_status = $_GET['trade_status'];
            if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
            }
            else {
                echo "trade_status=".$_GET['trade_status'];
            }
            return view('paysuccess',['order_sn'=>$_GET['out_trade_no']]);
        }
        else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
           // echo 'fail';
            return view('payfail');
        }
    }

}






