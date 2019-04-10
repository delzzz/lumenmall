<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

Class DBExpress extends Model{
    //订阅物流推送信息
    public function subscribeExpress($orderId){
        $shippingInfo = \DB::table('order')->where('order_id',$orderId)->first();
        $shippingName = $shippingInfo->shipping_name;
        $shippingCode = $shippingInfo->shipping_code;
        $shipperCode = \DB::table('express')->where('express_name',$shippingName)->value('express_code');
        $requestData="{'ShipperCode':'$shipperCode',
        'LogisticCode':'$shippingCode',
        'CallBack':'$orderId'
        }";
        $datas = array(
            'EBusinessID' => '1314059',
            'RequestType' => '1008',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, '665b109d-75da-4eff-908e-f95653ad9545');
        $result=$this->sendPost('http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx', $datas);
        return $result;
    }

    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }


}