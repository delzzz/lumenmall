<html>
<head>
    <script src="{{  Url::asset('/js/jquery-1.7.1.min.js') }}"></script>
</head>
<body>

{{--<div class="dv1">--}}
        {{--<!--<img src="http://test.lumen.com/getVerifyCode">-->--}}
        {{--<form action="http://cishoo.test.qyuedai.com/pay" method="post">--}}
            {{--<input type="hidden" name="order_id" value="1" />--}}
            {{--<input type="submit" value="提交">--}}
        {{--</form>--}}
    {{--</div>--}}
{{--<br/>--}}

<font color="#9ACD32"><b>该笔订单支付金额为<span style="color:#f00;font-size:50px">1分</span>钱</b></font><br/><br/>
<div align="center">
    <button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >立即支付</button>
</div>
<script type="text/javascript">
var orderId = 23;
    //调用微信JS api 支付
    function jsApiCall(data)
    {
        WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                data,
                function(res){
                    WeixinJSBridge.log(res.err_msg);
                    //alert(res.err_code+res.err_desc+res.err_msg);
//                    if (msg == "get_brand_wcpay_request:ok") {
//                        location.href = "/"+orderId;
//                    } else {
//                        if (msg == "get_brand_wcpay_request:cancel") {
//                            var err_msg = "您取消了微信支付";
//                        } else if (res.err_code == 3) {
//                            var err_msg = "您正在进行跨号支付正在为您转入扫码支付......";
//                        } else if (msg == "get_brand_wcpay_request:fail") {
//                            var err_msg = "微信支付失败错误信息：" + res.err_desc;
//                        } else {
//                            var err_msg = msg + "" + res.err_desc;
//                        }
//                        alert(err_msg);
//                    }
                }
        );
    }

    function callpay()
    {
        $.ajax({
            url: "http://cishoo.test.vrnav.cc/weixinpay",
            data:{'order_id':orderId},
            type: 'post',
            success: function (res) {
                res = eval("("+res+")");
                if (typeof WeixinJSBridge == "undefined"){
                    if( document.addEventListener ){
                        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                    }else if (document.attachEvent){
                        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                    }
                }else{
                    jsApiCall(res);
                }
            }
        });


    }
</script>

</body>
</html>