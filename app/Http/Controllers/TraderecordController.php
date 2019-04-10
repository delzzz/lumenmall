<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DBCategory;

class TraderecordController extends Controller
{
    /**
     * 查询所有
     */
    public function selectAll(Request $request)
    {
        $searchKey = $request->input('search_key');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $payMethod = $request->input('pay_method');
        $orderSource = $request->input('order_source');
        $type = $request->input('type');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $totalCount = \DB::table('trade_record as trade')
            ->join('order','order.order_id','=','trade.order_id')
            ->leftJoin('return_goods as return','trade.order_id','=','return.order_id')
            ->leftJoin('order_goods as goods','trade.order_id','=','goods.order_id')
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($startTime,$endTime){
                if($startTime !=='' || !empty($startTime)){
                    $query->where('trade.create_time', '>', $startTime);
                }
                if($endTime !=='' || !empty($endTime)){
                    $query->where('trade.create_time', '<', $endTime);
                }
            })
            ->where(function ($query) use ($payMethod){
                if($payMethod !=='' || !empty($payMethod)){
                    $query->where('order.pay_method',$payMethod);
                }
            })
            ->where(function ($query) use ($orderSource){
                if($orderSource !=='' || !empty($orderSource)){
                    $query->where('order.order_source',$orderSource);
                }
            })
            ->where(function ($query) use ($type){
                if($type !=='' || !empty($type)){
                    $query->where('trade.type',$type);
                }
            })
            ->distinct('trade.order_id')
            ->count('trade.order_id');
        $recordList = \DB::table('trade_record as trade')
            ->join('order','order.order_id','=','trade.order_id')
            ->leftJoin('return_goods as return','trade.order_id','=','return.order_id')
            ->leftJoin('order_goods as goods','trade.order_id','=','goods.order_id')
            ->select('order.order_sn','order.order_source','order.pay_method','trade.*')
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('order.order_sn', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($startTime,$endTime){
                if($startTime !=='' || !empty($startTime)){
                    $query->where('trade.create_time', '>', $startTime);
                }
                if($endTime !=='' || !empty($endTime)){
                    $query->where('trade.create_time', '<', $endTime);
                }
            })
            ->where(function ($query) use ($payMethod){
                if($payMethod !=='' || !empty($payMethod)){
                    $query->where('order.pay_method',$payMethod);
                }
            })
            ->where(function ($query) use ($orderSource){
                if ($orderSource !== '' && !empty($orderSource)) {
                    $query->where('order.order_source', $orderSource - 1);
                }
            })
            ->where(function ($query) use ($type){
                if($type !=='' || !empty($type)){
                    $query->where('trade.type',$type);
                }
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
            ->groupBy('trade.order_id')
            ->get();

        foreach ($recordList as $key => $value) {
            if ($value->type == 0) {
                //退款支出
               $goods = \DB::table('return_goods as return')
                   ->select('spu.goods_name','sku.spec','return.return_total','order.goods_num')
                   ->join('goods_sku as sku','sku.goods_id','=','return.goods_id')
                   ->join('goods_spu as spu','sku.spu_id','=','spu.spu_id')
                   ->join('order_goods as order','order.goods_id','=','return.goods_id')
                   ->where(['return_status'=>4,'return.order_id'=>$value->order_id])
                   ->get();
            } elseif ($value->type == 1) {
                //用户付款收入
                $goods = \DB::table('order_goods as goods')
                    ->select('spu.goods_name','sku.spec','goods.goods_num',\DB::raw("goods.price * goods.goods_num as goods_money"))
                    ->join('goods_sku as sku','goods.goods_id','=','sku.goods_id')
                    ->join('goods_spu as spu','goods.spu_id','=','spu.spu_id')
                    ->where(['goods.order_id'=>$value->order_id])
                    ->get();
            }
            $value->goods = $goods;
        }
        $tradeList['totalCount'] = $totalCount;
        $tradeList['itemList'] = $recordList;
        return json_encode($tradeList, JSON_UNESCAPED_UNICODE);
    }


}