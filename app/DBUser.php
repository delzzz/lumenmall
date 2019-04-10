<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class DBUser extends Model{
    //收货地址列表
    public function getAddressList($userId){
        return $addressList = \DB::table('user_address')
            ->where(['user_id'=>$userId,'is_delete'=>0])
            ->orderBy('is_default','desc')
            ->get();
    }

    //7天后自动确认收货
    public function confirmCollection(){
        $sevenDays = date('Y-m-d H:i:s', strtotime('-6 days'));
        $ConfirmingOrders = \DB::table('order')
            ->where('shipping_time', '<', $sevenDays)
            ->where(['order_status'=> 0,'shipping_status'=> 1,'pickup'=>0])
            ->pluck('order_id');
        foreach ($ConfirmingOrders as $key => $value) {
            \DB::table('order')->where('order_id', $value)->update([
                'shipping_status' => 2,
                'confirm_time' => date('Y-m-d H:i:s'),
                'confirm_status' => 1
            ]);
        }
    }

    //3天自动关闭订单
    public function closeOrder(){
        $threeDays = date('Y-m-d H:i:s', strtotime('-3 days'));
        $ClosingOrders = \DB::table('order')
            ->where('create_time', '<', $threeDays)
            ->where(['order_status'=> 0,'pay_status'=>0])
            ->pluck('order_id');
        foreach ($ClosingOrders as $key => $value) {
            \DB::table('order')->where('order_id', $value)->update([
                'order_status'=>2
            ]);
        }
    }

    //判断用户密码是否正确
    public function checkPassword($userId,$password){
        $psd = \DB::table('user')->where('user_id',$userId)->value('password');
        if($psd !== md5($password)){
            return false;
        }
        else{
            return true;
        }
    }

    //获取用户登录次数
    public function getUserLoginCount($mobile){
        $todayStart = date('Y-m-d H:i:s', time() - 60 * 30);
        $todayEnd = date('Y-m-d H:i:s');
        $count = \DB::table('user_log')
            ->where('mobile', $mobile)
            ->whereBetween('last_login', [$todayStart, $todayEnd])
            ->count();
        return $count;
    }
}