<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DBRegion;

class ShippingController extends Controller
{
    /**
     * @param Request $request
     * @return string
     * 查询快递公司
     */
    public function selectExpress(Request $request){
        $express = \DB::table('express')->get();
        return json_encode($express,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 查询所有运费模板
     */
    public function selectAll(Request $request)
    {
        if ($request->isMethod('post')) {
            $model_name = $request->input('model_name');
            $moduleList = \DB::table('shipping')
                ->join('shipping_mode', 'shipping.shipping_id', '=', 'shipping_mode.shipping_id')
                ->where(function ($query) use ($model_name) {
                    if ($model_name != '' || !empty($model_name)) {
                        $query->where('model_name', 'like', '%' . $model_name . '%');
                    }
                })
                ->where('shipping_mode.is_default', 1)
                ->where('shipping.is_delete', 0)
                ->select('shipping.*', 'shipping_mode.first_piece', 'shipping_mode.first_amount', 'shipping_mode.region_ids')
                ->orderBy('shipping.shipping_id', 'asc')
                ->get();
            $regionModel = new DBRegion();
            foreach ($moduleList as $key => &$value) {
                foreach ($value as $k => &$v) {
                    if ($k == 'region_ids') {
                        $v = $regionModel->getRegionName($v);
                    }
                }
            }
            return json_encode($moduleList, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 查询模板
     */
    public function selectOne(Request $request, $shippingId)
    {
        $model = \DB::table('shipping')->where('shipping_id', $shippingId)->get();
        $carry = \DB::table('shipping_mode')->where('shipping_id', $shippingId)->get();
        $regionModel = new DBRegion();
        $carry = json_decode($carry, true);
        foreach ($carry as $key => &$value) {
            foreach ($value as $k => &$v) {
                if ($k == 'region_ids') {
                    $value['region_name'] = $regionModel->getRegionName($v);
                }
            }
        }
        $shippingList['model'] = $model[0];
        $shippingList['carry'] = $carry;
        return json_encode($shippingList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 新增/修改运费模板
     */
    public function add(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'shipping.model.model_name' => 'required',
                //'shipping.model.shipping_name' => 'required',
                'shipping.model.valuation' => 'required'
            ]);
            $model = $request->input('shipping.model');
            $carry = $request->input('shipping.carry');
            foreach ($carry as $key => $value) {
                $this->validate($request, [
                    'shipping.carry.' . $key . '.is_default' => 'required',
                    'shipping.carry.' . $key . '.first_amount' => 'required',
                    'shipping.carry.' . $key . '.first_piece' => 'required',
                    'shipping.carry.' . $key . '.second_piece' => 'required',
                    'shipping.carry.' . $key . '.second_amount' => 'required',
                    'shipping.carry.' . $key . '.region_ids' => 'required'
                ]);
            }
            //判断增加还是修改
            $shippingId = $request->input('shipping.shipping_id');
            if (empty($shippingId) || $shippingId == '') {
                //新增
                $shippingId = \DB::table('shipping')->insertGetId(
                    [
                        'model_name' => $model['model_name'],
                        'shipping_name' => $model['shipping_name'],
                        'valuation' => $model['valuation'],
                        'create_time' => date('Y-m-d H:i:s')
                    ]
                );
            } else {
                //修改
                \DB::table('shipping')->where('shipping_id', $shippingId)->update(
                    [
                        'model_name' => $model['model_name'],
                        'shipping_name' => $model['shipping_name'],
                        'valuation' => $model['valuation']
                    ]
                );
                //删除原来的配送方式
                \DB::table('shipping_mode')->where('shipping_id', $shippingId)->delete();
            }
            //插入配送方式
            foreach ($carry as $key => $value) {
                $id = \DB::table('shipping_mode')->insertGetId(
                    [
                        'is_default' => $value['is_default'],
                        'shipping_id' => $shippingId,
                        'first_amount' => $value['first_amount'],
                        'first_piece' => $value['first_piece'],
                        'second_piece' => $value['second_piece'],
                        'second_amount' => $value['second_amount'],
                        'region_ids' => $value['region_ids']
                    ]
                );
            }
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    /**
     * 修改运费模板状态
     */
    public function changeStatus(Request $request)
    {
        $this->validate($request, [
            'shipping_id' => 'required',
            'status' => 'required',
        ]);
        $shipping_id = $request->input('shipping_id');
        $status = $request->input('status');
        $id = \DB::table('shipping')->where('shipping_id', $shipping_id)->update(['status' => $status]);
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    /**
     * 删除运费模板
     */
    public function delete(Request $request)
    {
        $this->validate($request, [
            'shipping_id' => 'required',
        ]);
        $shipping_id = $request->input('shipping_id');
        $id = \DB::table('shipping')->where('shipping_id', $shipping_id)->update(['is_delete' => 1]);
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    /**
     * 根据商品计算物流费用
     */
    public function getShippingInfo(Request $request)
    {
        $goods = $request->input('goods');
        $province = $request->input('province');
        $pieceArr = array();
        $weightArr = array();
        $arr = array();
        foreach ($goods as $key => $value) {
            $arr[$value['spu_id']][] = $value;
        }
        foreach ($arr as $key => $value) {
            $newarr[$key]['goods_num'] = 0;
            foreach ($value as $k => $v) {
                $newarr[$key]['goods_id'][] = $v['goods_id'];
                $newarr[$key]['sku_num'][] = $v['goods_num'];
                $newarr[$key]['goods_num'] += $v['goods_num'];
            }
        }
        foreach ($newarr as $ky => $vl) {
            $goodsNum = $vl['goods_num'];
            $shippingInfo = \DB::table('goods_spu as spu')
                ->join('shipping', 'spu.shipping_id', '=', 'shipping.shipping_id')
                ->join('shipping_mode as mode', 'shipping.shipping_id', '=', 'mode.shipping_id')
                ->select('spu.shipping_id', 'shipping.model_name', 'shipping.shipping_name', 'shipping.valuation', 'mode.*')
                ->where('spu.spu_id', $ky)
                ->get();
            foreach ($shippingInfo as $k => $v) {
                $regionArr = explode(',', $v->region_ids);
                foreach ($regionArr as $index => $region) {
                    //配送地区所属模板
                    if ($province == $region) {
                        //判断按件还是按重量计费
                        if ($v->valuation == 1) {
                            //按数量
                            if ($v->first_piece >= $goodsNum) {
                                //件数内 默认运费
                                $shippingFee = $v->first_amount;
                            } else {
                                //按件数追加运费
                                $overCount = $goodsNum - $v->first_piece;
                                if ($v->second_piece >= $overCount) {
                                    //续件大于超出件数
                                    $addFee = $v->second_amount;
                                } else {
                                    if ($v->second_piece == 1) {
                                        //每+1件 +运费
                                        $addFee = $overCount * $v->second_amount;
                                    } elseif ($overCount % $v->second_piece == 0) {
                                        //整除
                                        $addFee = ($overCount / $v->second_piece) * $v->second_amount;
                                    } else {
                                        //非整除 续件+1
                                        $addFee = floor(($overCount / $v->second_piece)) * $v->second_amount + $v->second_amount;
                                    }
                                }
                                $shippingFee = $v->first_amount + $addFee;
                            }
                            $pieceArr[$ky]['model_name'] = $v->model_name;
                            $pieceArr[$ky]['shipping_name'] = $v->shipping_name;
                            $pieceArr[$ky]['shipping_fee'] = $shippingFee;
                        } else {
                            //按重量
                            //判断商品规格有没有重量
                            $count = \DB::table('spec_info')->where(['spu_id' => $ky, 'spec_id' => 7])->count();
                            if ($count > 0) {
                                $weight = 0;
                                foreach ($vl['goods_id'] as $i => $goodsId) {
                                    $spec = \DB::table('goods_sku')->where('goods_id', $goodsId)->value('spec');
                                    $specArr = json_decode($spec);
                                    foreach ($specArr as $skey => $svalue) {
                                        //判断是否包含重量
                                        if ($svalue->id == 7 && $svalue->name == '重量') {
                                            $weight += $svalue->value * $vl['sku_num'][$i];
                                        }
                                    }
                                }
                                //首重内 默认运费
                                $shippingFee2 = $v->first_amount;
                                if ($v->first_piece < $weight) {
                                    //超出重量
                                    $overWeight = $weight - $v->first_piece;
                                    if ($v->second_piece > $overWeight) {
                                        //续重大于超重 续费
                                        $addFee2 = $v->second_amount;
                                    } else {
                                        if ($v->second_piece == 1) {
                                            //每+1kg +运费
                                            $addFee2 = $overWeight * $v->second_amount;
                                        } elseif ($overWeight % $v->second_piece == 0) {
                                            //整除
                                            $addFee2 = ($overWeight / $v->second_piece) * $v->second_amount;
                                        } else {
                                            //非整除 续件+1
                                            $addFee2 = floor(($overWeight / $v->second_piece)) * $v->second_amount + $v->second_amount;
                                        }
                                    }
                                    $shippingFee2 += $addFee2;
                                }
                                $weightArr[$ky]['model_name'] = $v->model_name;
                                $weightArr[$ky]['shipping_name'] = $v->shipping_name;
                                $weightArr[$ky]['shipping_fee'] = $shippingFee2;
                            }
                        }
                    }
                }
            }
        }
        //返回邮费最大值所在数组
        $feeArr = array_merge($pieceArr, $weightArr);
        $max = $feeArr[0]['shipping_fee'];
        $idx = 0;
        foreach ($feeArr as $k => $v) {
            if ($v['shipping_fee'] > $max) {
                $idx = $k;
            }
        }
        return $feeArr[$idx];
    }
//    public function getShippingInfo2(Request $request)
//    {
//        if ($request->isMethod('post')) {
//            $orderId = $request->input('order_id');
//            $spuIds = \DB::table('order')->join('order_goods as goods', 'goods.order_id', '=', 'order.order_id')
//                ->select('spu_id')->where('order.order_id', $orderId)->distinct()->get();
//            $shippingDetail = array();
//            //判断订单自取还是配送
//            $order = \DB::table('order')->select('pickup', 'province')->where('order.order_id', $orderId)->first();
//            if ($order->pickup == 1) {
//                //自取
//                $shippingDetail['pickup'] = 1;
//                $shippingDetail['shipping_fee'] = 0;
//                return $shippingDetail;
//            }
//            //$shippingFee = 0;
//            // $addFee = 0;
//            // $shippingFee2 = 0;
//            $addFee2 = 0;
//            $pieceArr = array();
//            $weightArr = array();
//            foreach ($spuIds as $key => $value) {
//                //var_dump($value);dd();
//                $shippingInfo = \DB::table('goods_spu as spu')
//                    ->join('shipping', 'spu.shipping_id', '=', 'shipping.shipping_id')
//                    ->join('shipping_mode as mode', 'shipping.shipping_id', '=', 'mode.shipping_id')
//                    ->select('spu.shipping_id', 'shipping.valuation', 'mode.*')
//                    ->where('spu.spu_id', $value->spu_id)
//                    ->get();
//                //订单商品数
//                $goodsNum = \DB::table('order')
//                    ->join('order_goods as goods', 'goods.order_id', '=', 'order.order_id')
//                    ->where(['order.order_id' => $orderId, 'goods.spu_id' => $value->spu_id])
//                    ->sum('goods_num');
//                foreach ($shippingInfo as $k => $v) {
//                    $regionArr = explode(',', $v->region_ids);
//                    foreach ($regionArr as $index => $region) {
//                        //配送地区所属模板
//                        if ($order->province == $region) {
//                            //判断按件还是按重量计费
//                            if ($v->valuation == 1) {
//                                //按数量
//                                if ($v->first_piece >= $goodsNum) {
//                                    //件数内 默认运费
//                                    $shippingFee = $v->first_amount;
//                                } else {
//                                    //按件数追加运费
//                                    $overCount = $goodsNum - $v->first_piece;
//                                    if ($v->second_piece >= $overCount) {
//                                        //续件大于超出件数
//                                        $addFee = $v->second_amount;
//                                    } else {
//                                        if ($v->second_piece == 1) {
//                                            //每+1件 +运费
//                                            $addFee = $overCount * $v->second_amount;
//                                        } elseif ($overCount % $v->second_piece == 0) {
//                                            //整除
//                                            $addFee = ($overCount / $v->second_piece) * $v->second_amount;
//                                        } else {
//                                            //非整除 续件+1
//                                            $addFee = floor(($overCount / $v->second_piece)) * $v->second_amount + $v->second_amount;
//                                        }
//                                    }
//                                    $shippingFee = $v->first_amount + $addFee;
//                                }
//                                $pieceArr[] = $shippingFee;
//                            } else {
//                                //按重量
//                                //判断商品规格有没有重量
//                                $count = \DB::table('spec_info')->where(['spu_id' => $value->spu_id, 'spec_id' => 7])->count();
//                                if ($count > 0) {
//                                    //订单重量
//                                    $goodsSpec = \DB::table('order')
//                                        ->join('order_goods as goods', 'goods.order_id', '=', 'order.order_id')
//                                        ->join('goods_sku as sku', 'sku.goods_id', '=', 'goods.goods_id')
//                                        ->where(['order.order_id' => $orderId, 'goods.spu_id' => $value->spu_id])
//                                        ->select('sku.spec')
//                                        ->get();
//                                    $weight = 0;
//                                    foreach ($goodsSpec as $skey => $svalue) {
//                                        $specArr = json_decode($svalue->spec);
//                                        foreach ($specArr as $sk => $sv) {
//                                            //判断是否包含重量
//                                            if ($sv->id == 7 && $sv->name == '重量') {
//                                                $weight += $sv->value*$goodsNum;
//                                            }
//                                        }
//                                    }
//                                    if ($v->first_piece >= $weight) {
//                                        //首重内 默认运费
//                                        $shippingFee2 = $v->first_amount;
//                                    } else {
//                                        //超出重量
//                                        $overWeight = $weight - $v->first_piece;
//                                        if ($v->second_piece > $overWeight) {
//                                            //续重大于超重 续费
//                                            $addFee2 = $v->second_amount;
//                                        } else {
//                                            if ($v->second_piece == 1) {
//                                                //每+1kg +运费
//                                                $addFee2 = $overWeight * $v->second_amount;
//                                            } elseif ($overWeight % $v->second_piece == 0) {
//                                                //整除
//                                                $addFee2 = ($overWeight / $v->second_piece) * $v->second_amount;
//                                            } else {
//                                                //非整除 续件+1
//                                                $addFee2 = floor(($overWeight / $v->second_piece)) * $v->second_amount + $v->second_amount;
//                                            }
//                                        }
//                                    }
//                                    $shippingFee2 = $v->first_amount + $addFee2;
//
//                                } else {
//                                    //按默认运费算
//                                    $shippingFee2 = $v->first_amount;
//                                }
//                                $weightArr[] = $shippingFee2;
//                            }
//                        }
//                    }
//                }
//            }
//            //返回邮费最大值
//            $feeArr = array_merge($pieceArr, $weightArr);
//            $pos = array_search(max($feeArr), $feeArr);
//            $shippingDetail['pickup'] = 0;
//            $shippingDetail['shipping_fee'] = $feeArr[$pos];
//            return $shippingDetail;
//        }
//    }
    //物流信息查询
    public function getExpressInfo(Request $request){
        $orderId = $request->input('order_id');
        $traces = \DB::table('express_info')->where('order_id',$orderId)->value('traces');
       return json_decode($traces);
    }

    //获取物流推送信息
    public function pushExpress(){
        $data = $_POST['RequestData'];
        $shippingInfo = json_decode($data);
        $expressInfo = $shippingInfo->Data;
        foreach ($expressInfo as $key => $value){
            $orderId = $value->CallBack;
            $shippingCode = $value->LogisticCode;
            $traces = json_encode($value->Traces);
            \DB::table('express_info')->insert([
                'order_id'=>$orderId,
               'shipping_code' => $shippingCode,
                'traces' => $traces
            ]);
        }
        $arr['EBusinessID'] = '1314059';
        $arr['UpdateTime'] = date('Y-m-d H:i:s');
        $arr['Success'] = true;
        return $arr;
    }

}