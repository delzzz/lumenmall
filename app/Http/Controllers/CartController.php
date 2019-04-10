<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DBGoods;
use App\DBPromotion;

class CartController extends Controller
{
    /**
     * 购物车列表
     */
    public function selectAll(Request $request, $userId)
    {
        $cartList = \DB::table('cart')
            ->select('sku.goods_img', \DB::raw('substring_index(spu.img,",",1) as img'), 'spu.goods_name', 'cart.*', 'sku.spec', 'spu.is_delete as spu_delete', 'sku.is_delete as sku_delete', 'spu.is_off',
                'spu.spu_id', 'spu.is_sale', 'sku.price', 'p.gap')
            ->join('goods_sku as sku', 'cart.goods_id', '=', 'sku.goods_id')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->leftJoin('promotion as p', 'p.spu_id', '=', 'spu.spu_id')
            ->where(['user_id' => $userId])
            ->groupBy('cart.cart_id')
            ->orderBy('cart.create_time', 'desc')
            ->get();
        //return $cartList;
        foreach ($cartList as $key => &$value) {
            if ($value->is_sale == 1) {
                //优惠
                $value->price = $value->price - $value->gap;
            }
            $value->goods_amount = $value->price * $value->goods_num;
            $spec = json_decode($value->spec);
            $specStr = '';
            foreach ($spec as $k => &$v) {
                $specStr .= $v->name . ':' . $v->value . ';';
            }
            $value->specStr = substr($specStr, 0, strlen($specStr) - 1);
        }
        return json_encode($cartList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 加入购物车
     */
    public function add(Request $request)
    {
        $userId = $request->input('user_id');
        $goodsId = $request->input('goods_id');
        //$goodsName = $request->get('goods_name');
        $goodsNum = $request->input('goods_num');
        //$spec = $request->get('spec');
        //$price = $request->get('price');
        //判断商品是否已存在
        $count = \DB::table('cart')->where(['user_id' => $userId, 'goods_id' => $goodsId])->count();
        if ($count > 0) {
            //已存在，改变数量
            $id = \DB::table('cart')->where(['user_id' => $userId, 'goods_id' => $goodsId])->increment('goods_num', $goodsNum);
        } else {
            //添加购物车
            $id = \DB::table('cart')->insertGetId([
                'user_id' => $userId,
                'goods_id' => $goodsId,
                //'goods_name' => $goodsName,
                'goods_num' => $goodsNum,
                'create_time' => date('Y-m-d H:i:s')
            ]);
        }
        $msg['success'] = true;
        $msg['data'] = $id;
        return $msg;
    }

    /**
     * 购物车商品数量变更
     */
    public function changeGoodsNum(Request $request)
    {
        if ($request->isMethod('post')) {
            $cartId = $request->input('cart_id');
            $goodsNum = $request->input('goods_num');
            $id = \DB::table('cart')->where('cart_id', $cartId)->update(['goods_num' => $goodsNum]);
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
    }

    /**
     * @param Request $request
     * 购物车删除
     */
    public function deleteCart(Request $request)
    {
        if ($request->isMethod('post')) {
            $cartIds = $request->input('cart_id');
            $cartArr = explode(',', $cartIds);
            foreach ($cartArr as $key => $value) {
                \DB::table('cart')->where('cart_id', $value)->delete();
            }
            $msg['success'] = true;
            return $msg;
        }
    }

    /**
     * @param Request $request
     * 确认商品信息
     */
    public function checkCart(Request $request)
    {
        if ($request->isMethod('post')) {
            $cartIds = $request->input('cart_id');
            $cartInfo = \DB::table('cart')
                ->select('sku.goods_img', \DB::raw('substring_index(spu.img,",",1) as img'), 'spu.goods_name', 'cart.*', 'sku.spec', 'spu.is_delete as spu_delete',
                    'sku.is_delete as sku_delete', 'spu.is_off', 'spu.spu_id', 'spu.is_sale', 'sku.price', 'p.gap', 'spu.is_pickup', 'spu.pickup_mobile', 'spu.pickup_address','spu.goods_sn')
                ->join('goods_sku as sku', 'cart.goods_id', '=', 'sku.goods_id')
                ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
                ->leftJoin('promotion as p', 'p.spu_id', '=', 'spu.spu_id')
                ->whereIn('cart_id', $cartIds)
                ->orderBy('cart.create_time', 'desc')
                ->groupBy('cart.cart_id')
                ->get();
            $flag = '';
            foreach ($cartInfo as $key => $value) {
                if ($value->is_pickup == 0) {
                    $flag .= 1;
                }
                if ($value->is_sale == 1) {
                    //优惠
                    $value->price = $value->price - $value->gap;
                }
                $value->total = $value->price * $value->goods_num;
                $spec = json_decode($value->spec);
                $specStr = '';
                foreach ($spec as $k => &$v) {
                    $specStr .= $v->name . ':' . $v->value . ';';
                }
                $value->specStr = substr($specStr, 0, strlen($specStr) - 1);
            }
            //包含有不支持上门取货的商品，就全都不支持
            if ($flag !== '') {
                //只支持快递
                $cartList['shipping_pickup'] = 0;
            } else {
                //可自取 可快递
                $cartList['shipping_pickup'] = 1;
            }
            $cartList['goods'] = $cartInfo;
            return json_encode($cartList, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param Request $request
     * 购物车商品总数
     */
    public function cartTotal(Request $request)
    {
        $userId = $request->input('user_id');
        return \DB::table('cart')->where('user_id', $userId)->count();
    }

}