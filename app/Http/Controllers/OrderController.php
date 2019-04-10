<?php

namespace App\Http\Controllers;

use App\DBExpress;
use App\DBGoods;
use App\DBRegion;
use App\DBStock;
use App\DBUser;
use App\Express;
use App\DBPromotion;
use Illuminate\Http\Request;
use App\DBCategory;
use App\Http\Controllers\ShippingController;

class OrderController extends Controller
{
    //生成订单
    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $basic = $request->input('basic');
            $goods = $request->input('goods');
            $orderSn = date('YmdHis') . rand(1000, 9999);
            $orderTotal = 0;
            $flagOff = '';
            foreach ($goods as $key => $value) {
                $isOff = \DB::table('goods_spu')->where('spu_id', $value['spu_id'])->value('is_off');
                if ($isOff == 1) {
                    $flagOff .= 1;
                }
                //计算价格
                $skuInfo = \DB::table('goods_spu as spu')
                    ->select('spu.spu_id', 'spu.is_sale', 'sku.price', 'p.gap', 'sku.goods_id')
                    ->join('goods_sku as sku', 'sku.spu_id', '=', 'spu.spu_id')
                    ->leftJoin('promotion as p', 'p.spu_id', '=', 'spu.spu_id')
                    ->where('goods_id', $value['goods_id'])
                    ->groupBy('spu.spu_id')
                    ->get();
                foreach ($skuInfo as $k => &$v) {
                    if ($v->is_sale == 1) {
                        $v->price = $v->price - $v->gap;
                    }
                    $orderTotal += $v->price * $value['goods_num'];
                }
                //$orderTotal += $goods[$key]['price'] * $goods[$key]['goods_num'];
            }
            if ($flagOff !== '') {
                //有下架商品
                $msg['data'] = '订单包含无效商品';
                $msg['success'] = false;
            } else {
                $orderTotal += $basic['shipping_fee'];
                //判断前端价格一致
//                if ($orderTotal != $basic['order_total']) {
//                    return '价格有误';
//                }
                if ($basic['pickup'] == 1) {
                    //自取 等待取货
                    $shippingStatus = 4;
                } else {
                    //快递
                    $shippingStatus = 0;
                }
                $orderId = \DB::table('order')->insertGetId([
                    'order_sn' => $orderSn,
                    'user_id' => $basic['user_id'],
                    'address_id' => $basic['address_id'],
                    'remark' => $basic['remark'],
                    'goods_amount' => $basic['goods_amount'],
                    'order_total' => $basic['order_total'],
                    'pickup' => $basic['pickup'],
                    'shipping_fee' => $basic['shipping_fee'],
                    'shipping_name' => $basic['shipping_name'],
                    'mode_name' => $basic['mode_name'],
                    'shipping_status' => $shippingStatus,
                    'consignee' => $basic['consignee'],
                    'mobile' => $basic['mobile'],
                    'zipcode' => $basic['zipcode'],
//                'province' => $basic['province'],
//                'city' => $basic['city'],
//                'county' => $basic['county'],
                    'address' => $basic['address'],
                    'order_source' => $basic['order_source'],
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $stockModel = new DBStock();

                foreach ($goods as $key => $value) {
                    $id = \DB::table('order_goods')->insertGetId([
                        'order_id' => $orderId,
                        'spu_id' => $goods[$key]['spu_id'],
                        'goods_id' => $goods[$key]['goods_id'],
                        'goods_name' => $goods[$key]['goods_name'],
                        'goods_sn' => $goods[$key]['goods_sn'],
                        'spec' => $goods[$key]['spec'],
                        'price' => $goods[$key]['price'],
                        'goods_num' => $goods[$key]['goods_num'],
                    ]);
                    //出库
                    $stockModel->outStock($goods[$key]['goods_id'], $goods[$key]['goods_num'], 0, 0, $orderSn);
                }
                $msg['data'] = $orderId;
                $msg['success'] = true;
            }
            return $msg;
        }
    }


    //更改支付状态
    public function changeOrderStatus(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required',
        ]);
        $orderId = $request->input('order_id');
        if ($request->isMethod('post')) {
            $id = \DB::table('order')->where('order_id', $orderId)->update([
                'pay_status' => 1
            ]);
            $orderTotal = \DB::table('order')->where('order_id', $orderId)->value('order_total');
            //交易明细记录
            \DB::table('trade_record')->insertGetId([
                'order_id' => $orderId,
                'money' => $orderTotal,
                'type' => 1, //0支出 1收入
                'create_time' => date('Y-m-d H:i:s')
            ]);
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //查询所有订单
    public function selectAll(Request $request)
    {
        if ($request->isMethod('post')) {
            $searchKey = $request->input('search_key');
            $consignee = $request->input('consignee');
            $pickup = $request->input('pickup');
            $orderSource = $request->input('order_source');
            $status = $request->input('status');
            $page = $request->input('page');
            $perPage = $request->input('perPage');
            $totalCount = \DB::table('order')
                ->select('order.order_id')
                ->join('user', 'user.user_id', 'order.user_id')
                ->join('order_goods', 'order_goods.order_id', 'order.order_id')
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                            ->orWhere('order_goods.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->where(function ($query) use ($consignee) {
                    if ($consignee !== '' || !empty($consignee)) {
                        $query->where('order.consignee', 'like', '%' . $consignee . '%')
                            ->orWhere('order.mobile', 'like', '%' . $consignee . '%');
                    }
                })
                ->where(function ($query) use ($pickup) {
                    if ($pickup !== '' && !empty($pickup)) {
                        $query->where('order.pickup', $pickup - 1);
                    }
                })
                ->where(function ($query) use ($orderSource) {
                    if ($orderSource !== '' && !empty($orderSource)) {
                        $query->where('order.order_source', $orderSource - 1);
                    }
                })
                ->where(function ($query) use ($status) {
                    if ($status !== '' && !empty($status)) {
                        if ($status == 1) {
                            //待付款
                            $query->where(['order_status' => 0, 'order.pay_status' => 0]);
                        } elseif ($status == 2) {
                            //待发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 0]);
                        } elseif ($status == 3) {
                            //已发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 1]);
                        } elseif ($status == 4) {
                            //已收货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 2]);
                        } elseif ($status == 5) {
                            //等待取货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 3]);
                        } elseif ($status == 6) {
                            //已取货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 4]);
                        }
                    }
                })
                ->where('order_status', '<>', 3)
                ->distinct('order.order_id')
                ->count('order.order_id');
            $orderInfo = \DB::table('order')
                ->join('user', 'user.user_id', 'order.user_id')
                ->join('order_goods', 'order_goods.order_id', 'order.order_id')
                ->select('order.order_id', 'order.order_sn', 'order.create_time', 'user.mobile as user_account', 'order.consignee', 'order.mobile',
                    'order.order_total', 'order.pay_method', 'order.order_status', 'order.pay_status', 'order.shipping_status')
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                            ->orWhere('order_goods.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->where(function ($query) use ($consignee) {
                    if ($consignee !== '' || !empty($consignee)) {
                        $query->where('order.consignee', 'like', '%' . $consignee . '%')
                            ->orWhere('order.mobile', 'like', '%' . $consignee . '%');
                    }
                })
                ->where(function ($query) use ($pickup) {
                    if ($pickup !== '' && !empty($pickup)) {
                        $query->where('order.pickup', $pickup - 1);
                    }
                })
                ->where(function ($query) use ($orderSource) {
                    if ($orderSource !== '' && !empty($orderSource)) {
                        $query->where('order.order_source', $orderSource - 1);
                    }
                })
                ->where(function ($query) use ($status) {
                    if ($status !== '' && !empty($status)) {
                        if ($status == 1) {
                            //待付款
                            $query->where(['order_status' => 0, 'order.pay_status' => 0]);
                        } elseif ($status == 2) {
                            //待发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 0]);
                        } elseif ($status == 3) {
                            //已发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 1]);
                        } elseif ($status == 4) {
                            //已收货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 2]);
                        } elseif ($status == 5) {
                            //等待取货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 3]);
                        } elseif ($status == 6) {
                            //已取货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 4]);
                        }
                    }
                })
                ->where('order_status', '<>', 3)
                ->when(true, function ($query) use ($page, $perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->offset(($page - 1) * $perPage);
                    }
                })
                ->when(true, function ($query) use ($perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->limit($perPage);
                    }
                })
                ->distinct()
                ->get();
            $orderList['totalCount'] = $totalCount;
            $orderList['itemList'] = $orderInfo;
            return json_encode($orderList, JSON_UNESCAPED_UNICODE);
        }
    }

    //订单详情
    public function selectOne(Request $request, $orderId)
    {
        $order = \DB::table('order')->where('order_id', $orderId)
            ->join('user', 'user.user_id', '=', 'order.user_id')
            //->join('user_address as address','order.address_id','=','address.address_id')
            ->select('order.order_id', 'order.order_sn', 'order.create_time', 'user.mobile as account', 'user.nickname', 'user.gender', 'order.pay_method', 'order.order_source', 'pickup',
                'order.order_status', 'order.shipping_status', 'order.consignee', 'order.mobile', 'address', 'order.zipcode', 'order.remark',
                'goods_amount', 'order_total', 'shipping_fee', 'mode_name', 'shipping_name', 'shipping_code')->first();
        $modelRegion = new DBRegion();
        //$consigneeAddress = $modelRegion->getFullRegionName($order->province,$order->city,$order->county).$order->address;
        //$order->consignee_address = $consigneeAddress;
        $goods = \DB::table('order_goods')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'order_goods.spu_id')
            ->join('goods_sku as sku', 'sku.goods_id', 'order_goods.goods_id')
            ->where('order_id', $orderId)
            ->select(\DB::raw('substring_index(spu.img,",",1) as img'), 'sku.goods_img', 'order_goods.*', 'spu.pickup_mobile', 'spu.pickup_address')
            ->get();
        $orderDetail['basic'] = $order;
        $orderDetail['goods'] = $goods;
        $orderStatus = \DB::table('order')->where('order_id', $orderId)->value('order_status');
        if ($orderStatus == 3) {
            //退货状态
            $returnGoods = \DB::table('return_goods')
                ->select('create_time', 'goods_id', 'reason', 'remark', 'imgs', 'return_total', 'return_status')
                ->where('order_id', $orderId)
                ->get();
            //  $returnGoods = json_decode($returnGoods, true);
            $orderDetail['return_goods'] = $returnGoods;
        }
        return json_encode($orderDetail, JSON_UNESCAPED_UNICODE);
    }

    //修改订单
    public function editOrder(Request $request)
    {
        if ($request->isMethod('post')) {
            $basic = $request->input('basic');
            $goods = $request->input('goods');
            $goodsAmount = 0;
            foreach ($goods as $key => $value) {
                $goodsAmount += $goods[$key]['price'] * $goods[$key]['goods_num'];
            }
            if ($basic['goods_amount'] != $goodsAmount) {
                $msg['data'] = '商品价格不一致';
                $msg['success'] = false;
            } else {
                $orderDetail = \DB::table('order')->where('order_id', $basic['order_id'])->first();
                $payStatus = $orderDetail->pay_status;
                $shippingStatus = $orderDetail->shipping_status;
                if ($payStatus !== 0 || $shippingStatus !== 0) {
                    $msg['data'] = '当前状态不可编辑';
                    $msg['success'] = true;
                } else {
                    \DB::table('order')->where('order_id', $basic['order_id'])->update([
                        'consignee' => $basic['consignee'],
                        'mobile' => $basic['mobile'],
                        'address' => $basic['address'],
                        'zipcode' => $basic['zipcode'],
                        'goods_amount' => $goodsAmount,
                        'order_total' => $goodsAmount + $orderDetail->shipping_fee,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                    foreach ($goods as $key => $value) {
                        $id = \DB::table('order_goods')->where('order_goods_id', $goods[$key]['order_goods_id'])->update([
                            'price' => $goods[$key]['price']
                        ]);
                    }
                    $msg['data'] = $id;
                    $msg['success'] = true;
                }
            }
            return $msg;
        }
    }

    //关闭订单
    public function close(Request $request)
    {
        $orderId = $request->input('order_id');
        //$orderArr = explode(',', $orderId);
        //foreach ($orderArr as $key => $value) {
        $id = \DB::table('order')->where('order_id', $orderId)->update(['order_status' => 2]);
        //入库
        $goods = \DB::table('order')
            ->join('order_goods as goods', 'order.order_id', '=', 'goods.order_id')
            ->select('goods.goods_id', 'goods.goods_num', 'order.order_sn', 'goods.spu_id')
            ->where('order.order_id', $orderId)
            ->get();
        $stockModel = new DBStock();
        foreach ($goods as $key => $value) {
            \DB::table('goods_sku')->where('goods_id', $value->goods_id)->increment('stock', $value->goods_num);
            \DB::table('goods_spu')->where('spu_id', $value->spu_id)->increment('stock', $value->goods_num);
            //入库
            $stockModel->inStock($value->goods_id, $value->goods_num, 3, 0, $value->order_sn);
        }
        $this->operateOrderLog($orderId, 2, null, null, '关闭订单');
        //}
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    //用户取消订单
    public function cancelOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        $id = \DB::table('order')->where('order_id', $orderId)->update(['order_status' => 1]);
        $goods = \DB::table('order')
            ->join('order_goods as goods', 'order.order_id', '=', 'goods.order_id')
            ->select('goods.goods_id', 'goods.goods_num', 'order.order_sn', 'goods.spu_id')
            ->where('order.order_id', $orderId)
            ->get();
        //商品入库
        $stockModel = new DBStock();
        foreach ($goods as $key => $value) {
            \DB::table('goods_sku')->where('goods_id', $value->goods_id)->increment('stock', $value->goods_num);
            \DB::table('goods_spu')->where('spu_id', $value->spu_id)->increment('stock', $value->goods_num);
            $stockModel->inStock($value->goods_id, $value->goods_num, 2, 0, $value->order_sn);
        }
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    //发货
    public function deliverGoods(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required',
            'consignee' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'zipcode' => 'required',
            'shipping_code' => 'required',
        ]);
        $orderId = $request->input('order_id');
        $consignee = $request->input('consignee');
        $mobile = $request->input('mobile');
        $address = $request->input('address');
        $zipcode = $request->input('zipcode');
        $shippingCode = $request->input('shipping_code');
        $id = \DB::table('order')->where('order_id', $orderId)->update([
            'consignee' => $consignee,
            'mobile' => $mobile,
            'address' => $address,
            'zipcode' => $zipcode,
            'shipping_code' => $shippingCode,
            'shipping_status' => 1,
            'shipping_time' => date('Y-m-d H:i:s')
        ]);
        $this->operateOrderLog($orderId, 0, 1, null, '发货');
        //订阅物流推送
        $modelExpress = new DBExpress();
        $modelExpress->subscribeExpress($orderId);
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    //自取确认信息
    public function pickupDetail(Request $request)
    {
        $orderId = $request->input('order_id');
        $detail = \DB::table('order')
            ->join('user', 'order.user_id', '=', 'user.user_id')
            ->join('order_goods', 'order.order_id', '=', 'order_goods.order_id')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'order_goods.spu_id')
            ->select('order_sn', 'order.create_time', 'user.mobile', 'user.nickname', 'user.gender', 'order.order_source', 'pay_method', 'spu.pickup_address', 'spu.pickup_mobile')
            ->where('order.order_id', $orderId)
            ->first();
        return json_encode($detail, JSON_UNESCAPED_UNICODE);
    }

    //自取确定已取货
    public function changePickupStatus(Request $request)
    {
        $orderId = $request->input('order_id');
        $id = \DB::table('order')->where('order_id', $orderId)->update([
            'shipping_status' => 4,
            'confirm_time' => date('Y-m-d H:i:s'),
            'confirm_status' => 2
        ]);
        $this->operateOrderLog($orderId, 0, 4, null, '确定用户已取货');
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    //用户确定收货
    public function changeCollectStatus(Request $request)
    {
        $orderId = $request->input('order_id');
        $password = $request->input('password');
        $userId = $request->input('user_id');
        $userModel = new DBUser();
        if (!$userModel->checkPassword($userId, $password)) {
            $msg['data'] = '密码错误';
            $msg['success'] = false;
        } else {
            $id = \DB::table('order')->where('order_id', $orderId)->update([
                'shipping_status' => 2,
                'confirm_time' => date('Y-m-d H:i:s'),
                'confirm_status' => 0
            ]);
            $msg['data'] = $id;
            $msg['success'] = true;
        }
        return $msg;
    }

    //已完成列表
    public function selectAllDone(Request $request)
    {
        if ($request->isMethod('post')) {
            $searchKey = $request->input('search_key');
            $consignee = $request->input('consignee');
            $pickup = $request->input('pickup');
            $orderSource = $request->input('order_source');
            $page = $request->input('page');
            $perPage = $request->input('perPage');
            $totalCount = \DB::table('order')
                ->join('user', 'user.user_id', 'order.user_id')
                ->join('order_goods', 'order_goods.order_id', 'order.order_id')
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                            ->orWhere('order_goods.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->where(function ($query) use ($consignee) {
                    if ($consignee !== '' || !empty($consignee)) {
                        $query->where('order.consignee', 'like', '%' . $consignee . '%')
                            ->orWhere('order.mobile', 'like', '%' . $consignee . '%');
                    }
                })
                ->where(function ($query) use ($pickup) {
                    if ($pickup !== '' && !empty($pickup)) {
                        $query->where('order.pickup', $pickup - 1);
                    }
                })
                ->where(function ($query) use ($orderSource) {
                    if ($orderSource !== '' && !empty($orderSource)) {
                        $query->where('order.order_source', $orderSource - 1);
                    }
                })
                ->where(function ($query) {
                    //已收货/已取货
                    $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 2])
                        ->orWhere(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 4]);
                })
                ->distinct('order.order_id')
                ->count('order.order_id');
            $orderInfo = \DB::table('order')
                ->join('user', 'user.user_id', 'order.user_id')
                ->join('order_goods', 'order_goods.order_id', 'order.order_id')
                ->select('order.order_id', 'order.order_sn', 'order.confirm_time', 'user.mobile as user_account', 'order.consignee', 'order.mobile',
                    'order.order_total', 'order.pay_method', 'order.order_status', 'order.shipping_status', 'order.confirm_status')
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                            ->orWhere('order_goods.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->where(function ($query) use ($consignee) {
                    if ($consignee !== '' || !empty($consignee)) {
                        $query->where('order.consignee', 'like', '%' . $consignee . '%')
                            ->orWhere('order.mobile', 'like', '%' . $consignee . '%');
                    }
                })
                ->where(function ($query) use ($pickup) {
                    if ($pickup !== '' && !empty($pickup)) {
                        $query->where('order.pickup', $pickup - 1);
                    }
                })
                ->where(function ($query) use ($orderSource) {
                    if ($orderSource !== '' && !empty($orderSource)) {
                        $query->where('order.order_source', $orderSource - 1);
                    }
                })
                ->where(function ($query) {
                    //已收货/已取货
                    $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 2])
                        ->orWhere(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 4]);
                })
                ->when(true, function ($query) use ($page, $perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->offset(($page - 1) * $perPage);
                    }
                })
                ->when(true, function ($query) use ($perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->limit($perPage);
                    }
                })
                ->distinct()
                ->get();
            $orderList['totalCount'] = $totalCount;
            $orderList['itemList'] = $orderInfo;
            return json_encode($orderList, JSON_UNESCAPED_UNICODE);
        }
    }

    //退货列表
    public function returnList(Request $request)
    {
        if ($request->isMethod('post')) {
            $searchKey = $request->input('search_key');
            $consignee = $request->input('consignee');
            $status = $request->input('status');
            $page = $request->input('page');
            $perPage = $request->input('perPage');
            $returnIdStr = array();
            $totalCount = \DB::table('order')
                ->join('user', 'user.user_id', 'order.user_id')
                ->join('order_goods', 'order_goods.order_id', 'order.order_id')
                ->join('return_goods as return', 'order.order_id', 'return.order_id')
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                            ->orWhere('order_goods.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->where(function ($query) use ($consignee) {
                    if ($consignee !== '' || !empty($consignee)) {
                        $query->where('order.consignee', 'like', '%' . $consignee . '%')
                            ->orWhere('order.mobile', 'like', '%' . $consignee . '%');
                    }
                })
                ->where(function ($query) use ($status, $returnIdStr) {
                    if ($status !== '' && !empty($status)) {
                        if ($status == 1) {
                            //申请退货
                            $returnIds = \DB::table('return_goods')->select('order_id')->where('return_status', 1)->get();
                            foreach ($returnIds as $key => $value) {
                                $returnIdStr[] = $value->order_id;
                            }
                            $query->whereIn('order.order_id', $returnIdStr);

                        } elseif ($status == 2) {
                            //退货中
                            $query->where(['return_status' => 2]);
                        } elseif ($status == 3) {
                            //拒绝退货
                            $query->where(['return_status' => 3]);
                        } elseif ($status == 4) {
                            //已退货
                            $query->where(['return_status' => 4]);
                        } elseif ($status == 5) {
                            //部分退货
                            $returnIds = \DB::table('return_goods')->select('order_id')->where('return_status', 1)->get();
                            foreach ($returnIds as $key => $value) {
                                $returnIdStr[] = $value->order_id;
                            }
                            $query->whereNotIn('order.order_id', $returnIdStr)->where('part_return', 1);
                        }
                    }
                })
                ->where('order_status', 3)
                ->distinct('order.order_id')
                ->count('order.order_id');

            $orderInfo = \DB::table('order')
                ->join('user', 'user.user_id', 'order.user_id')
                ->join('order_goods', 'order_goods.order_id', 'order.order_id')
                ->join('return_goods as return', 'order.order_id', 'return.order_id')
                ->select('order.order_id', 'order.order_sn', 'user.mobile as user_account', 'order.consignee', 'order.mobile',
                    'order.pay_method', 'return.return_status')
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                            ->orWhere('order_goods.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->where(function ($query) use ($consignee) {
                    if ($consignee !== '' || !empty($consignee)) {
                        $query->where('order.consignee', 'like', '%' . $consignee . '%')
                            ->orWhere('order.mobile', 'like', '%' . $consignee . '%');
                    }
                })
                ->where(function ($query) use ($status, $returnIdStr) {
                    if ($status !== '' && !empty($status)) {
                        if ($status == 1) {
                            //申请退货
                            $returnIds = \DB::table('return_goods')->select('order_id')->where('return_status', 1)->get();
                            foreach ($returnIds as $key => $value) {
                                $returnIdStr[] = $value->order_id;
                            }
                            $query->whereIn('order.order_id', $returnIdStr);

                        } elseif ($status == 2) {
                            //退货中
                            $query->where(['return_status' => 2]);
                        } elseif ($status == 3) {
                            //拒绝退货
                            $query->where(['return_status' => 3]);
                        } elseif ($status == 4) {
                            //已退货
                            $query->where(['return_status' => 4]);
                        } elseif ($status == 5) {
                            //部分退货
                            $returnIds = \DB::table('return_goods')->select('order_id')->where('return_status', 1)->get();
                            foreach ($returnIds as $key => $value) {
                                $returnIdStr[] = $value->order_id;
                            }
                            $query->whereNotIn('order.order_id', $returnIdStr)->where('part_return', 1);
                        }
                    }
                })
                ->where('order_status', 3)
                ->when(true, function ($query) use ($page, $perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->offset(($page - 1) * $perPage);
                    }
                })
                ->when(true, function ($query) use ($perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->limit($perPage);
                    }
                })
                ->groupBy('order_id')
                ->get();
            foreach ($orderInfo as $key => &$value) {
                $return_total = 0;
                $applyTime = \DB::table('return_goods')->where('order_id', $value->order_id)->select('create_time')->orderBy('create_time', 'desc')->first();
                $answerTime = \DB::table('return_goods')->where('order_id', $value->order_id)->select('answer_time')->orderBy('answer_time', 'desc')->first();
                $returnTotal = \DB::table('return_goods')->where('order_id', $value->order_id)->select('return_total')->get();
                foreach ($returnTotal as $k => $v) {
                    $return_total += $v->return_total;
                    $value->return_total = $return_total;
                }
                $value->apply_time = $applyTime->create_time;
                $value->answer_time = $answerTime->answer_time;
            }
            $orderList['totalCount'] = $totalCount;
            $orderList['itemList'] = $orderInfo;
            return json_encode($orderList, JSON_UNESCAPED_UNICODE);
        }
    }

    //申请退货
    public function returnGoods(Request $request)
    {
        if ($request->isMethod('post')) {
            $orderId = $request->input('order_id');
            $goodsId = $request->input('goods_id');
            $goodsIdArr = explode(',', $goodsId);
            $returnTotal = $request->input('return_total');
            $remark = $request->input('remark');
            $reason = $request->input('reason');
            $imgs = $request->input('imgs');
            foreach ($goodsIdArr as $key => $value) {
                $count = \DB::table('return_goods')->where(['order_id' => $orderId, 'goods_id' => $value])->count();
                if ($count > 0) {
                    return '不可重复申请退货';
                }
                \DB::table('order')->where('order_id', $orderId)->update([
                    'order_status' => 3,
                ]);
                \DB::table('order_goods')->where(['order_id' => $orderId, 'goods_id' => $value])->update([
                    'is_return' => 1,
                ]);
                $id = \DB::table('return_goods')->insertGetId([
                    'order_id' => $orderId,
                    'goods_id' => $value,
                    'reason' => $reason,
                    'return_total' => $returnTotal,
                    'remark' => $remark,
                    'imgs' => $imgs,
                    'create_time' => date('Y-m-d H:i:s'),
                    'return_status' => 1
                ]);
            }
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //同意/拒绝退货
    public function operateReturn(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'return_id' => 'required',
                'flag' => 'required'
            ]);
            $returnIds = $request->input('return_id');
            $flag = $request->input('flag');
            $orderId = $request->input('order_id');
            $returnIdArr = explode(',', $returnIds);
            $returnStatus = $flag == 1 ? 2 : 3;
            foreach ($returnIdArr as $key => $value) {
                $id = \DB::table('return_goods')->where('return_id', $value)->update([
                    'return_status' => $returnStatus,
                    'answer_time' => date('Y-m-d H:i:s')
                ]);
            }
            $order = \DB::table('order')->select('shipping_status')->where('order_id', $orderId)->first();
            $this->operateOrderLog($orderId, 3, $order->shipping_status, $returnStatus, $returnStatus == 2 ? '同意退货' : '拒绝退货');
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //同意/拒绝退货(款)列表
    public function operateReturnList(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'order_id' => 'required',
                'flag' => 'required'
            ]);
            $orderId = $request->input('order_id');
            $flag = $request->input('flag');
            //flag 0:退货 1:退款   return_status 1:申请中 2:退货中
            $returnStatus = $flag == 1 ? 2 : 1;
            $agreeReturnList = \DB::table('return_goods as goods')->select('return_id', 'goods_id')->where(['order_id' => $orderId, 'return_status' => $returnStatus])->get();
            foreach ($agreeReturnList as $key => &$value) {
                $goods = \DB::table('order_goods')
                    ->join('goods_spu as spu', 'spu.spu_id', '=', 'order_goods.spu_id')
                    ->join('goods_sku as sku', 'sku.goods_id', 'order_goods.goods_id')
                    ->where(['order_id' => $orderId, 'order_goods.goods_id' => $value->goods_id])
                    ->select(\DB::raw('substring_index(spu.img,",",1) as img'), 'sku.goods_img', 'order_goods.*')
                    ->first();
                $value->img = $goods->img;
                $value->goods_img = $goods->goods_img;
                $value->goods_name = $goods->goods_name;
                $value->goods_sn = $goods->goods_sn;
                $value->spec = $goods->spec;
                $value->goods_num = $goods->goods_num;
                $value->price = $goods->price;
            }
            return json_encode($agreeReturnList, JSON_UNESCAPED_UNICODE);
        }
    }

    //同意/拒绝退款
    public function operateRefund(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'order_id' => 'required',
                'return_id' => 'required',
                'flag' => 'required'
            ]);
            $returnIds = $request->input('return_id');
            $flag = $request->input('flag');
            $returnIdArr = explode(',', $returnIds);
            $orderId = $request->input('order_id');
            $order = \DB::table('order')->where('order_id', $orderId)
                ->join('user', 'user.user_id', '=', 'order.user_id')
                ->select('pay_method', 'order.mobile', 'shipping_status')->first();
            $total = 0;
            foreach ($returnIdArr as $key => $value) {
                $return = \DB::table('return_goods')->where('return_id', $value)
                    ->select('return_total')->first();
                $total += $return->return_total;
            }
            if ($flag == 1) {
                //同意 判断调用支付接口
                $returnStatus = 4;
                if ($order->pay_method == 1) {
                    //微信

                } else {
                    //支付宝

                }
                //交易明细记录
                \DB::table('trade_record')->insertGetId([
                    'order_id' => $orderId,
                    'money' => $total,
                    'type' => 0, //0支出 1收入
                    'create_time' => date('Y-m-d H:i:s')
                ]);
                //退货入库
                foreach ($returnIdArr as $key => $value) {
                    $return = \DB::table('return_goods as return')
                        ->join('order_goods as goods', 'goods.order_id', '=', 'return.order_id')
                        ->join('order', 'order.order_id', '=', 'return.order_id')
                        ->where('return_id', $value)
                        ->whereColumn('goods.goods_id', 'return.goods_id')
                        ->select('return.goods_id', 'order.order_sn', 'goods.goods_num', 'goods.spu_id')
                        ->first();
                    //库存增加
                    \DB::table('goods_sku')->where('goods_id', $return->goods_id)->increment('stock', $return->goods_num);
                    \DB::table('goods_spu')->where('spu_id', $return->spu_id)->increment('stock', $return->goods_num);
                    //入库记录
                    $stockModel = new DBStock();
                    $stockModel->inStock($return->goods_id, $return->goods_num, 0, 0, $return->order_sn);
                }
            } else {
                //不同意
                $returnStatus = 5;
            }
            foreach ($returnIdArr as $key => $value) {
                $id = \DB::table('return_goods')->where('return_id', $value)->update([
                    'return_status' => $returnStatus,
                    'answer_time' => date('Y-m-d H:i:s')
                ]);
            }
            $goodsCount = \DB::table('order_goods')->where('order_id', $orderId)->count();
            $returnCount = \DB::table('return_goods')->where(['order_id' => $orderId, 'return_status' => 4])->count();
            //判断部分还是全部退货
            if ($goodsCount == $returnCount) {
                $id = \DB::table('order')->where('order_id', $orderId)->update([
                    'part_return' => 0
                ]);
            } else {
                $id = \DB::table('order')->where('order_id', $orderId)->update([
                    'part_return' => 1
                ]);
            }
            $this->operateOrderLog($orderId, 3, $order->shipping_status, $returnStatus, $returnStatus == 4 ? '同意退款' : '拒绝退款');
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //确认退款信息
    public function confirmRefund(Request $request)
    {
        $orderId = $request->input('order_id');
        $returnIds = $request->input('return_id');
        $order = \DB::table('order')->where('order_id', $orderId)
            ->join('user', 'user.user_id', '=', 'order.user_id')
            ->select('pay_method', 'order.mobile')->first();
        $returnIdArr = explode(',', $returnIds);
        $total = 0;
        foreach ($returnIdArr as $key => $value) {
            $return = \DB::table('return_goods')->where('return_id', $value)
                ->select('return_total')->first();
            $total += $return->return_total;
        }
        $info['pay_method'] = $order->pay_method;
        $info['total'] = $total;
        return json_encode($info, JSON_UNESCAPED_UNICODE);
    }

    //操作日志
    public function operateOrderLog($orderId, $orderStatus, $shippingStatus = null, $returnStatus = null, $remark = null)
    {
        \DB::table('order_log')->insertGetId([
            'order_id' => $orderId,
            'action_user' => 1, //user_id
            'order_status' => $orderStatus,
            'shipping_status' => $shippingStatus,
            'return_status' => $returnStatus,
            'remark' => $remark,
            'create_time' => date('Y-m-d H:i:s')
        ]);
    }

    //商家概况
    public function overview(Request $request)
    {
        //今日订单数
        $overviewArr = array();
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d H:i:s');
        $todayOrderCount = \DB::table('order')->whereBetween('create_time', [$todayStart, $todayEnd])->count();
        //今日销售总额
        $todaySalesTotal = \DB::table('order')->select(\DB::raw('sum(goods_amount) as sales_total'))
            ->where('pay_status', 1)
            ->first()->sales_total;
        //昨日销售总额
        $yesterdayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $yesterdayEnd = date('Y-m-d 11:59:59', strtotime('-1 day'));
        $yesterdaySalesTotal = \DB::table('order')->select(\DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$yesterdayStart, $yesterdayEnd])
            ->where('pay_status', 1)
            ->first()->sales_total;
        //本月销售总额
        $monthSalesTotal = \DB::table('order')->select(\DB::raw('sum(goods_amount) as sales_total'))
            ->where(\DB::raw('DATE_FORMAT(create_time,"%Y%m")'), \DB::raw('DATE_FORMAT( CURDATE() , "%Y%m" )'))
            ->where('pay_status', 1)
            ->first()->sales_total;
        //待发货订单数量
        $undeliverCount = \DB::table('order')->where(['shipping_status' => 0, 'order_status' => 0, 'pay_status' => 1])->count();
        //待付款订单数
        $unpaidCount = \DB::table('order')->where(['order_status' => 0, 'pay_status' => 0])->count();
        //待处理退货订单数
        $pendingReturnCount = \DB::table('order')->join('return_goods as return', 'order.order_id', '=', 'return.order_id')
            ->where(['order.order_status' => 3, 'order.pay_status' => 1, 'return.return_status' => 0])->distinct('return.order_id')->count('return.order_id');
        //昨日订单数
        $yesterdayOrderCount = \DB::table('order')->whereBetween('create_time', [$yesterdayStart, $yesterdayEnd])->count();
        //广告位即将结束 5天内结束
        $ending = date('Y-m-d 00:00:00', strtotime('+4 day'));
        $closingBannerCount = \DB::table('banner')
            ->where('end_time', '<=', $ending)
            ->where('end_time', '>=', $todayStart)
            ->count();
        //一周销量
        $sevenDays = date('Y-m-d H:i:s', strtotime('-6 days'));
        $weekSalesTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$sevenDays, $todayEnd])
            ->where('pay_status', 1)
            ->groupBy('days')
            ->get();
        //一个月销量
        $thirtyDays = date('Y-m-d H:i:s', strtotime('-29 days'));
        $monthSalesTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$thirtyDays, $todayEnd])
            ->where('pay_status', 1)
            ->groupBy('days')
            ->get();
        //已下架商品总数
        $offGoodsCount = \DB::table('goods_spu')->where('is_off', 1)->where('is_delete', 0)->count();
        //紧张库存
        $warningGoodsCount = \DB::table('goods_sku as sku')
            ->join('goods_spu as spu', 'sku.spu_id', '=', 'spu.spu_id')
            ->where(\DB::raw('CAST(sku.stock AS SIGNED INTEGER)'), '<=', \DB::raw('CAST(sku.warning_stock AS SIGNED INTEGER)'))
            ->where(['sku.is_delete' => 0, 'spu.is_delete' => 0, 'spu.is_off' => 0])
            ->distinct('sku.spu_id')
            ->count('sku.spu_id');
        //商品分类数量
        $categoryModel = new DBCategory();
        $categoryCount = $categoryModel->getCategoryCount();
        //在售商品件数
        $notForSale = \DB::table('goods_spu')->where('set_time_sell', 1)->where('start_time', '>', $todayStart)->pluck('spu_id');
        $goodsForSale = \DB::table('goods_spu')
            ->where(['is_off' => 0, 'is_delete' => 0])
            ->whereNotIn('spu_id', $notForSale)
            ->count();
        //已出售商品数
        $salesCount = \DB::table('order')
            ->join('order_goods as goods', 'goods.order_id', '=', 'order.order_id')
            ->where('pay_status', 1)->sum('goods.goods_num');
        //累计订单数
        $orderCount = \DB::table('order')->count();
        //0移动端 1PC端 2钱悦贷PC 3钱悦贷 4慈硕智慧机器人
        $source1OrderCount = \DB::table('order')->where('order_source', 0)->count();
        $source2OrderCount = \DB::table('order')->where('order_source', 1)->count();
        $source3OrderCount = \DB::table('order')->where('order_source', 2)->count();
        $source4OrderCount = \DB::table('order')->where('order_source', 3)->count();
        $source5OrderCount = \DB::table('order')->where('order_source', 4)->count();
        $sourceOrderCount['source1OrderCount'] = $source1OrderCount;
        $sourceOrderCount['source2OrderCount'] = $source2OrderCount;
        $sourceOrderCount['source3OrderCount'] = $source3OrderCount;
        $sourceOrderCount['source4OrderCount'] = $source4OrderCount;
        $sourceOrderCount['source5OrderCount'] = $source5OrderCount;
        //7天30天销量趋势图
        $source1WeekTrend = \DB::table('order')
            ->select('order_id', \DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$sevenDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 0)
            ->groupBy('days')
            ->get();
        $source2WeekTrend = \DB::table('order')
            ->select('order_id', \DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$sevenDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 1)
            ->groupBy('days')
            ->get();
        $source3WeekTrend = \DB::table('order')
            ->select('order_id', \DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$sevenDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 2)
            ->groupBy('days')
            ->get();
        $source4WeekTrend = \DB::table('order')
            ->select('order_id', \DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$sevenDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 3)
            ->groupBy('days')
            ->get();
        $source5WeekTrend = \DB::table('order')
            ->select('order_id', \DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$sevenDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 4)
            ->groupBy('days')
            ->get();
        $sourceWeekTrends['source1WeekTrend'] = $source1WeekTrend;
        $sourceWeekTrends['source2WeekTrend'] = $source2WeekTrend;
        $sourceWeekTrends['source3WeekTrend'] = $source3WeekTrend;
        $sourceWeekTrends['source4WeekTrend'] = $source4WeekTrend;
        $sourceWeekTrends['source5WeekTrend'] = $source5WeekTrend;
        $source1MonthTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$thirtyDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 0)
            ->groupBy('days')
            ->get();
        $source2MonthTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$thirtyDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 1)
            ->groupBy('days')
            ->get();
        $source3MonthTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$thirtyDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 2)
            ->groupBy('days')
            ->get();
        $source4MonthTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$thirtyDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 3)
            ->groupBy('days')
            ->get();
        $source5MonthTrend = \DB::table('order')
            ->select(\DB::raw('DATE_FORMAT(create_time,"%Y-%m-%d") days'), \DB::raw('sum(goods_amount) as sales_total'))
            ->whereBetween('create_time', [$thirtyDays, $todayEnd])
            ->where('pay_status', 1)
            ->where('order_source', 4)
            ->groupBy('days')
            ->get();
        $sourceMonthTrends['source1MonthTrend'] = $source1MonthTrend;
        $sourceMonthTrends['source2MonthTrend'] = $source2MonthTrend;
        $sourceMonthTrends['source3MonthTrend'] = $source3MonthTrend;
        $sourceMonthTrends['source4MonthTrend'] = $source4MonthTrend;
        $sourceMonthTrends['source5MonthTrend'] = $source5MonthTrend;

        $overviewArr['todayOrderCount'] = $todayOrderCount;
        $overviewArr['todaySalesTotal'] = $todaySalesTotal;
        $overviewArr['yesterdaySalesTotal'] = $yesterdaySalesTotal;
        $overviewArr['monthSalesTotal'] = $monthSalesTotal;
        $overviewArr['undeliverCount'] = $undeliverCount;
        $overviewArr['unpaidCount'] = $unpaidCount;
        $overviewArr['pendingReturnCount'] = $pendingReturnCount;
        $overviewArr['yesterdayOrderCount'] = $yesterdayOrderCount;
        $overviewArr['closingBannerCount'] = $closingBannerCount;
        $overviewArr['weekSalesTrend'] = $weekSalesTrend;
        $overviewArr['monthSalesTrend'] = $monthSalesTrend;
        $overviewArr['offGoodsCount'] = $offGoodsCount;
        $overviewArr['warningGoodsCount'] = $warningGoodsCount;
        $overviewArr['categoryCount'] = $categoryCount;
        $overviewArr['goodsForSale'] = $goodsForSale;
        $overviewArr['salesCount'] = $salesCount;
        $overviewArr['orderCount'] = $orderCount;
        $overviewArr['sourceOrderCount'] = $sourceOrderCount;
        $overviewArr['sourceWeekTrends'] = $sourceWeekTrends;
        $overviewArr['sourceMonthTrends'] = $sourceMonthTrends;
        return $overviewArr;
    }

    //用户订单
    public function userOrder(Request $request)
    {
        if ($request->isMethod('post')) {
            $userId = $request->input('user_id');
            $status = $request->input('status');
            $page = $request->input('page');
            $perPage = $request->input('perPage');
            $totalCount = \DB::table('order')
                ->where(function ($query) use ($status) {
                    if ($status !== '' && !empty($status)) {
                        if ($status == 1) {
                            //待付款
                            $query->where(['order_status' => 0, 'order.pay_status' => 0]);
                        } elseif ($status == 2) {
                            //待发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 0]);
                        } elseif ($status == 3) {
                            //已发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 1]);
                        } elseif ($status == 4) {
                            //已收货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 2]);
                        } elseif ($status == 5) {
                            //申请售后
                            $query->where(['order_status' => 3]);
                        }
                    }
                })
                ->where(['user_id' => $userId, 'is_delete' => 0])
                ->count();
            $orderInfo = \DB::table('order')
                ->select('order_id', 'create_time', 'order_source', 'order_sn', 'goods_amount',
                    'order_total', 'shipping_fee', 'pickup', 'pay_method', 'order_status', 'pay_status', 'shipping_status', 'shipping_time')
                ->where(function ($query) use ($status) {
                    if ($status !== '' && !empty($status)) {
                        if ($status == 1) {
                            //待付款
                            $query->where(['order_status' => 0, 'order.pay_status' => 0]);
                        } elseif ($status == 2) {
                            //待发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 0]);
                        } elseif ($status == 3) {
                            //已发货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 1]);
                        } elseif ($status == 4) {
                            //已收货
                            $query->where(['order_status' => 0, 'order.pay_status' => 1, 'order.shipping_status' => 2]);
                        } elseif ($status == 5) {
                            //申请售后
                            $query->where(['order_status' => 3]);
                        }
                    }
                })
                ->where(['user_id' => $userId, 'is_delete' => 0])
                ->when(true, function ($query) use ($page, $perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->offset(($page - 1) * $perPage);
                    }
                })
                ->when(true, function ($query) use ($perPage) {
                    if ($perPage != '' || !empty($perPage)) {
                        $query->limit($perPage);
                    }
                })
                ->get();
            foreach ($orderInfo as $key => $value) {
                $goodsInfo = \DB::table('order_goods as goods')
                    ->join('goods_spu as spu', 'spu.spu_id', '=', 'goods.spu_id')
                    ->join('goods_sku as sku', 'sku.goods_id', '=', 'goods.goods_id')
                    ->select(\DB::raw('substring_index(spu.img,",",1) as img'), 'sku.goods_img', 'goods.goods_name', 'goods.spec', 'spu.is_sale', 'goods.price as real_price', 'sku.price',
                        'goods.goods_num', 'goods.is_return', \DB::raw('goods.price*goods.goods_num as total'), 'is_select','goods.goods_id')
                    ->where('order_id', $value->order_id)->get();
                $value->goods_info = $goodsInfo;
            }
            $orderList['totalCount'] = $totalCount;
            $orderList['itemList'] = $orderInfo;
            return json_encode($orderList, JSON_UNESCAPED_UNICODE);
        }
    }

    //用户删除订单
    public function deleteOrder(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'order_id' => 'required',
            ]);
            $orderId = $request->input('order_id');
            $id = \DB::table('order')->where('order_id', $orderId)->update(['is_delete' => 1]);
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }


}