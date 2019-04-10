<?php

namespace App\Http\Controllers;

use App\DBGoods;
use App\DBPromotion;
use App\DBStock;
use Illuminate\Http\Request;
use App\DBGoodsSpec;

class GoodsController extends Controller
{
    /**
     * 商品列表
     */
    public function selectAll(Request $request)
    {
        $sort = $request->input('sort');
        $categoryPid = $request->input('category_pid');
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $isPromotion = $request->input('is_promotion');
        $isRecommend = $request->input('is_recommend');
        $setTimeStart = $request->input('set_time_start');
        $position = $request->input('position');
        $level = array(
            'direction' => 'SORT_ASC',
            'field' => 'spu_id',
        );
        //排序
        if ($sort != '' && !empty($sort)) {
            if ($sort == 1) {
                //价格从高到低
                $level = array(
                    'direction' => 'SORT_DESC',
                    'field' => 'price',
                );
            } elseif ($sort == 2) {
                //价格从低到高
                $level = array(
                    'direction' => 'SORT_ASC',
                    'field' => 'price',
                );

            } elseif ($sort == 3) {
                //销量从高到低
                $level = array(
                    'direction' => 'SORT_DESC',
                    'field' => 'sales_total',
                );
            } elseif ($sort == 4) {
                //销量从低到高
                $level = array(
                    'direction' => 'SORT_ASC',
                    'field' => 'sales_total',
                );
            }
        }
        $totalCount = \DB::table('goods_spu as spu')
            ->join('brand as b','spu.brand_id','=','b.brand_id')
            ->join('category as c','spu.category_id','=','c.category_id')
            ->join('category as cc','cc.category_id','=','c.pid')
            ->leftJoin('promotion as p','spu.spu_id','=','p.spu_id')
            ->leftJoin('recommendation as r','r.spu_id','=','spu.spu_id')
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($categoryPid) {
                if ($categoryPid !== '' && !empty($categoryPid)) {
                    $query->where('c.pid',$categoryPid);
                }
            })
            ->where(function ($query) use ($isPromotion) {
                if ($isPromotion !== '' || !empty($isPromotion)) {
                    if($isPromotion==1){
                        $query->where('spu.is_sale',1);
                    }
                    else{
                        $query->where('spu.is_sale',0);
                    }
                }
            })
            ->where(function ($query) use ($isRecommend,$position) {
                if ($isRecommend !== '' || !empty($isRecommend)) {
                    if($isRecommend == 1){
                        $query->where('spu.is_recommend',1);
                        if($position !== '' && !empty($position)){
                            $query->where('r.position','like','%'.$position.'%');
                        }
                    }
                    else{
                        $query->where('spu.is_recommend',0);
                    }
                }
            })
            ->where(function ($query) use ($setTimeStart) {
                if ($setTimeStart !== '' || !empty($setTimeStart)) {
                    if($setTimeStart==1){
                        $currentDate = date('Y-m-d');
                        $query->orWhere('spu.set_time_sell',0)->orWhere([['spu.set_time_sell','=',1],['start_time','<=',$currentDate]]);
                    }
                }
            })
            ->where(['is_off' => 0,'spu.is_delete'=>0])
            ->where('stock','>',0)
            ->distinct('spu.spu_id')
            ->count('spu.spu_id');
        $spuList =  \DB::table('goods_spu as spu')
           ->select(\DB::raw('substring_index(spu.img,",",1) as img'),'spu.spu_id','spu.goods_name','b.brand_name','spu.goods_sn','spu.goods_attr',
               'spu.price','spu.stock','cc.category_name','spu.l_price as min_price','spu.is_sale','spu.is_recommend','p.sales_price','r.position')
            ->join('brand as b','spu.brand_id','=','b.brand_id')
            ->join('category as c','spu.category_id','=','c.category_id')
            ->join('category as cc','cc.category_id','=','c.pid')
            ->leftJoin('promotion as p','spu.spu_id','=','p.spu_id')
            ->leftJoin('recommendation as r','r.spu_id','=','spu.spu_id')
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($categoryPid) {
                if ($categoryPid !== '' && !empty($categoryPid)) {
                    $query->where('c.pid',$categoryPid);
                }
            })
            ->where(function ($query) use ($isPromotion) {
                if ($isPromotion !== '' || !empty($isPromotion)) {
                    if($isPromotion==1){
                        $query->where('spu.is_sale',1);
                    }
                    else{
                        $query->where('spu.is_sale',0);
                    }
                }
            })
            ->where(function ($query) use ($isRecommend,$position) {
                if ($isRecommend !== '' || !empty($isRecommend)) {
                    if($isRecommend == 1){
                        $query->where('spu.is_recommend',1);
                        if($position !== '' && !empty($position)){
                            $query->where('r.position','like','%'.$position.'%');
                        }
                    }
                    else{
                        $query->where('spu.is_recommend',0);
                    }
                }
            })
           ->where(function ($query) use ($setTimeStart) {
               if ($setTimeStart !== '' || !empty($setTimeStart)) {
                   if($setTimeStart==1){
                       $currentDate = date('Y-m-d');
                       $query->orWhere('spu.set_time_sell',0)->orWhere([['spu.set_time_sell','=',1],['start_time','<=',$currentDate]]);
                   }
               }
           })
            ->where(['is_off' => 0,'spu.is_delete'=>0])
            ->where('stock','>',0)
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
            ->groupBy('spu.spu_id')
            ->get();

        foreach ($spuList as $key => &$value) {
            //计算销量
            $order = \DB::table('order_goods')
                ->select(\DB::raw('sum(order_goods.goods_num) as sales_total'))
                ->join('order', 'order.order_id', 'order_goods.order_id')
                ->where(['spu_id' => $value->spu_id, 'order.pay_status' => 1])
                ->first();
            $value->sales_total = $order->sales_total??0;
//            //规格图
//            $goodsImg = \DB::table('goods_sku as sku')
//                ->join('goods_spu as spu','sku.spu_id','=','spu.spu_id')
//                ->where(['spu.spu_id'=>$value->spu_id,'sku.is_delete'=>0])
//                ->pluck('goods_img');
//            $value->goods_img = $goodsImg;
        }
        $arrSort = array();
        foreach ($spuList as $k => $v) {
            foreach ($v as $nm => $vl) {
                $arrSort[$nm][$k] = $vl;
            }
        }
        $spuList = json_decode($spuList,true);
        if (!empty($spuList)) {
            array_multisort($arrSort[$level['field']], constant($level['direction']), $spuList);
        }
        $goodsList['totalCount'] = $totalCount;
        $goodsList['itemList'] = $spuList;
        return json_encode($goodsList, JSON_UNESCAPED_UNICODE);
    }

    //根据spu_id查询sku
    public function selectSku(Request $request, $spuId)
    {
        $goodsModel = new DBGoods();
        $skuList = $goodsModel->selectSku($spuId);
        return json_encode($skuList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 添加/修改商品信息
     */
    public function add(Request $request)
    {
        if ($request->isMethod('post')) {
            $spuId = $request->input('spu_id');
            $basic = $request->input('goods.basic');
            $others = $request->input('goods.others');
            $stock = $request->input('goods.stock');
            $spec = $request->input('goods.spec');
            $sku = $request->input('goods.sku');
            $img = $request->input('goods.img');
            //必填字段校验
            $this->validate($request, [
                'goods.basic.brand_id' => 'required',
                'goods.basic.category_id' => 'required',
                'goods.basic.goods_attr' => 'required',
                'goods.basic.goods_name' => 'required',
                'goods.basic.shipping_id' => 'required',
                'goods.basic.goods_description' => 'required',
                'goods.stock.goods_sn' => 'required',
                'goods.stock.price' => 'required',
                'goods.stock.stock' => 'required',
                'goods.others.set_time_sell' => 'required',
                'goods.others.is_pickup' => 'required',
            ]);
            if ($basic['keywords'] == '' || empty($basic['keywords'])) {
                $basic['keywords'] = '';
            }
            //设置售卖时间验证时间不为空
            if ($others['set_time_sell'] == 1) {
                $this->validate($request, [
                    'goods.others.start_time' => 'required|before:goods.others.end_time',
                    'goods.others.end_time' => 'required',
                ]);
            } else {
                $others['start_time'] = null;
                $others['end_time'] = null;
            }
            //支持自取验证地址手机不为空
            if ($others['is_pickup'] == 1) {
                $this->validate($request, [
                    'goods.others.pickup_address' => 'required',
                    'goods.others.pickup_mobile' => 'required',
                ]);
            } else {
                $others['pickup_address'] = null;
                $others['pickup_mobile'] = null;
            }
            //规格不为空判断
            if (count($spec) < 1) {
                return '规格为必填';
            }
            //sku验证非空判断
            foreach ($sku as $key => $value) {
                $this->validate($request, [
                    'goods.sku.' . $key . '.specs' => 'required',
                    'goods.sku.' . $key . '.price' => 'required',
                    'goods.sku.' . $key . '.sku_sn' => 'required',
                    'goods.sku.' . $key . '.stock' => 'required',
                    'goods.sku.' . $key . '.warning_stock' => 'required',
                ]);
            }
            //图片 >=5  <=7
            if (count($img) < 5 || count($img) > 7) {
                return '商品图片数量不对';
            }
            //商品主图排第一
            $imgArr = [];
            foreach ($img as $key => &$value) {
                foreach ($value as $k => $v) {
                    if ($value['is_main'] == 1) {
                        array_unshift($imgArr, $value['image_path']);
                        break;
                    } else {
                        array_push($imgArr, $value['image_path']);
                        break;
                    }
                }
            }
            if (empty($spuId) || $spuId == '') {
                //添加
                //插入goods_spu表
                $spuId = \DB::table('goods_spu')->insertGetId([
                    'category_id' => $basic['category_id'],
                    'brand_id' => $basic['brand_id'],
                    'goods_name' => $basic['goods_name'],
                    'shipping_id' => $basic['shipping_id'],
                    'goods_description' => $basic['goods_description'],
                    'keywords' => $basic['keywords'],
                    'goods_attr' => $basic['goods_attr'],
                    'goods_sn' => $stock['goods_sn'],
                    'price' => $stock['price'],
                    'stock' => $stock['stock'],
                    'set_time_sell' => $others['set_time_sell'],
                    'start_time' => $others['start_time'],
                    'end_time' => $others['end_time'],
                    'pickup_address' => $others['pickup_address'],
                    'pickup_mobile' => $others['pickup_mobile'],
                    'img' => implode(',', $imgArr),
                    'create_time' => date('Y-m-d H:i:s'),
                    'on_time' => date('Y-m-d H:i:s')
                ]);
                $msg['data'] = $spuId;
            } else {
                //修改商品
                //修改goods_spu表
                $updateId = \DB::table('goods_spu')->where('spu_id', $spuId)->update([
                    'category_id' => $basic['category_id'],
                    'brand_id' => $basic['brand_id'],
                    'goods_name' => $basic['goods_name'],
                    'shipping_id' => $basic['shipping_id'],
                    'goods_description' => $basic['goods_description'],
                    'keywords' => $basic['keywords'],
                    'goods_attr' => $basic['goods_attr'],
                    'goods_sn' => $stock['goods_sn'],
                    'price' => $stock['price'],
                    'stock' => $stock['stock'],
                    'set_time_sell' => $others['set_time_sell'],
                    'start_time' => $others['start_time'],
                    'end_time' => $others['end_time'],
                    'pickup_address' => $others['pickup_address'],
                    'pickup_mobile' => $others['pickup_mobile'],
                    'img' => implode(',', $imgArr),
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                //修改规格表
                \DB::table('spec_info')->where('spu_id', $spuId)->delete();
                //修改goods_sku表
                \DB::table('goods_sku')->where('spu_id', $spuId)->update(['is_delete' => 1]);
                $msg['data'] = $updateId;
            }
            //插入规格表
            foreach ($spec as $key => $value) {
                foreach ($value as $k => $v) {
                    $spec[$key][$k] = explode(',', $v);
                }
            }
            foreach ($spec as $key => $value) {
                foreach ($value as $k => $v) {
                    foreach ($v as $kk => $vv) {
                        \DB::table('spec_info')->insert([
                            'spec_id' => $k,
                            'spec_value' => $vv,
                            'spu_id' => $spuId
                        ]);
                    }
                }
            }

            //插入goods_sku表
            foreach ($sku as $key => $value) {
                \DB::table('goods_sku')->insert([
                    'spu_id' => $spuId,//$spuId,
                    'sku_sn' => $sku[$key]['sku_sn'],
                    'price' => $sku[$key]['price'],
                    'stock' => $sku[$key]['stock'],
                    'warning_stock' => $sku[$key]['warning_stock'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'goods_img' => $sku[$key]['goods_img'],
                    'spec' => $sku[$key]['specs']
                ]);
            }
            //修改总库存
            $stockModel = new DBStock();
            $stockModel->changeStock($spuId);
            //同一商品最高最低价
            $goodsModel = new DBGoods();
            $goodsModel->changeMinandMax($spuId);
            $msg['success'] = true;
            return $msg;
        }
    }

    //修改sku表
    public function editSku(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'goods_id' => 'required',
                'sku_sn' => 'required',
                'price' => 'required',
                'stock' => 'required',
                'warning_stock' => 'required',
            ]);
            $goodsId = $request->input('goods_id');
            $skuSn = $request->input('sku_sn');
            $price = $request->input('price');
            $stock = $request->input('stock');
            $warningStock = $request->input('warning_stock');
            $updateId = \DB::table('goods_sku')->where('goods_id', $goodsId)->update([
                'sku_sn' => $skuSn,
                'price' => $price,
                'stock' => $stock,
                'warning_stock' => $warningStock
            ]);
            $spu = \DB::table('goods_sku')->where('goods_id', $goodsId)->select('spu_id')->first();
            //修改总库存
            $stockModel = new DBStock();
            $stockModel->changeStock($spu->spu_id);
            //修改spu最高最低价
            $goodsModel = new DBGoods();
            $goodsModel->changeMinandMax($spu->spu_id);
            $msg['data'] = $updateId;
            $msg['success'] = true;
            return $msg;
        }
    }

    //查询商品详情
    public function selectOne(Request $request, $spuId)
    {
        if (empty($spuId) || $spuId == '') {
            return '缺少商品spu_id';
        }
        $goodsDetail = \DB::table('goods_spu as spu')
            ->join('category as c', 'c.category_id', '=', 'spu.category_id')
            ->join('category as d', 'c.pid', '=', 'd.category_id')
            ->join('brand as b', 'b.brand_id', '=', 'spu.brand_id')
            ->join('shipping as s', 's.shipping_id', '=', 'spu.shipping_id')
            ->leftJoin('promotion as p','spu.spu_id','=','p.spu_id')
            ->select('d.category_name as category_pname', 'c.category_name', 'b.brand_name', 'spu.*', 's.model_name','p.sales_price','p.gap')
            ->where('spu.spu_id',$spuId)
            ->first();
        $followNum = \DB::table('user_goods')->where('spu_id',$spuId)->count();
        $basic['goods_name'] = $goodsDetail->goods_name;
        $basic['category_pname'] = $goodsDetail->category_pname;
        $basic['category_name'] = $goodsDetail->category_name;
        $basic['category_id'] = $goodsDetail->category_id;
        $basic['brand_name'] = $goodsDetail->brand_name;
        $basic['brand_id'] = $goodsDetail->brand_id;
        $basic['keywords'] = $goodsDetail->keywords;
        $basic['goods_attr'] = $goodsDetail->goods_attr ;
        $basic['model_name'] = $goodsDetail->model_name;
        $basic['shipping_id'] = $goodsDetail->shipping_id;
        $basic['img'] = explode(',',$goodsDetail->img);
        $basic['goods_description'] = $goodsDetail->goods_description;
        $basic['is_sale'] = $goodsDetail->is_sale;
        $basic['sales_price'] = $goodsDetail->sales_price;
        $basic['gap'] = $goodsDetail->gap;
        $basic['min_price'] = $goodsDetail->l_price;
        $basic['max_price'] = $goodsDetail->h_price;
        $basic['follow_num'] = $followNum;
        $stock['goods_sn'] = $goodsDetail->goods_sn;
        $stock['price'] = $goodsDetail->price;
        $stock['stock'] = $goodsDetail->stock;
        $others['set_time_sell'] = $goodsDetail->set_time_sell;
        if ($others['set_time_sell'] == 1) {
            $others['start_time'] = $goodsDetail->start_time;
            $others['end_time'] = $goodsDetail->end_time;
        }
        $others['is_pickup'] = $goodsDetail->is_pickup;
        if ($others['is_pickup'] == 1) {
            $others['pickup_address'] = $goodsDetail->pickup_address;
            $others['pickup_mobile'] = $goodsDetail->pickup_mobile;
        }
        $spec = \DB::table('spec_info')->join('spec', 'spec.spec_id', '=', 'spec_info.spec_id')->where('spu_id', $spuId)->select('spec.spec_name', 'spec.spec_id', 'spec_info.spec_value')->get();
        $specArr = array();
        $spec = json_decode($spec, true);
        foreach ($spec as $key => $value) {
            $specArr[$value['spec_id']][$value['spec_name']][] = $value['spec_value'];
        }
        $goodsModel = new DBGoods();
        $skuList = $goodsModel->selectSku($spuId);
        $goods['basic'] = $basic;
        $goods['stock'] = $stock;
        $goods['others'] = $others;
        $goods['spec'] = $specArr;
        $goods['skuList'] = $skuList;
        return json_encode($goods, JSON_UNESCAPED_UNICODE);
    }

    //批量删除商品
    public function delete(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, ['spu_ids' => 'required']);
            $spuIds = $request->input('spu_ids');
            $spuArr = explode(',', $spuIds);
            foreach ($spuArr as $key => $value) {
                $id = \DB::table('goods_spu')->where('spu_id', $value)->update(['is_delete' => 1, 'delete_time' => date('Y-m-d H:i:s')]);
            }
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //批量下架商品
    public function withdraw(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, ['spu_ids' => 'required']);
            $spuIds = $request->input('spu_ids');
            $spuArr = explode(',', $spuIds);
            foreach ($spuArr as $key => $value) {
                $id = \DB::table('goods_spu')->where('spu_id', $value)->update(['is_off' => 1, 'update_time' => date('Y-m-d H:i:s')]);
            }
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //批量上架商品
    public function puton(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, ['spu_ids' => 'required']);
            $spuIds = $request->input('spu_ids');
            $spuArr = explode(',', $spuIds);
            foreach ($spuArr as $key => $value) {
                $id = \DB::table('goods_spu')->where('spu_id', $value)->update(['is_off' => 0, 'on_time' => date('Y-m-d H:i:s')]);
            }
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }

    //下架商品列表
    public function selectWithdraw(Request $request)
    {
        $sort = $request->input('sort');
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        //总销量
        if ($sort != '' && !empty($sort)) {
            if ($sort == 1) {
                //下架时间近到远
                $sort = 'desc';
            } elseif ($sort == 2) {
                //下架时间远到近
                $sort = 'asc';
            }
        }
        $totalCount = \DB::table('goods_spu')
            ->where('is_off', 1)
            ->where('is_delete', 0)
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->count();
        $offList = \DB::table('goods_spu')
            ->select('spu_id', 'goods_name', 'goods_sn', 'price', 'on_time', 'update_time')
            ->where('is_off', 1)
            ->where('is_delete', 0)
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->orderBy('update_time', $sort)
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
        foreach ($offList as $key => &$value) {
            //计算销量
            $order = \DB::table('order_goods')
                ->select(\DB::raw('sum(order_goods.goods_num) as sales_total'))
                ->join('order', 'order.order_id', 'order_goods.order_id')
                ->where(['spu_id' => $value->spu_id, 'order.pay_status' => 1])
                ->first();
            $value->sales_total = $order->sales_total??0;
        }
        $goodsList['totalCount'] = $totalCount;
        $goodsList['itemList'] = $offList;
        return json_encode($goodsList, JSON_UNESCAPED_UNICODE);
    }

    //商品回收站
    public function recycleGoods(Request $request)
    {
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $totalCount = \DB::table('goods_spu')
            ->where('is_delete', 1)
            ->orderBy('delete_time', 'desc')
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->count();
        $goodsList = \DB::table('goods_spu')
            ->select('spu_id', 'goods_name', 'goods_sn', 'price', 'stock', 'on_time', 'is_off', 'delete_time')
            ->where('is_delete', 1)
            ->orderBy('delete_time', 'desc')
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
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
            ->get();
        $recycleList['totalCount'] = $totalCount;
        $recycleList['itemList'] = $goodsList;
        return json_encode($recycleList, JSON_UNESCAPED_UNICODE);
    }


    //已售罄商品
    public function soldoutGoods(Request $request)
    {
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $totalCount = \DB::table('goods_sku as sku')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->where('sku.is_delete', 0)
            ->where('sku.stock', 0)
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->count();
        $skuList = \DB::table('goods_sku as sku')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->where('sku.is_delete', 0)
            ->where('sku.stock', 0)
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->select('spu.goods_name', 'spu.goods_sn', 'sku.goods_id', 'sku.spu_id', 'sku.sku_sn', 'sku.spec', 'sku.price', 'sku.stock', 'sku.warning_stock')
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
        $soldOutList['totalCount'] = $totalCount;
        $soldOutList['itemList'] = $skuList;
        return json_encode($soldOutList, JSON_UNESCAPED_UNICODE);
    }

    //删除sku
    public function deleteSku(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, ['goods_ids' => 'required']);
            $skuIds = $request->input('goods_ids');
            $spuArr = explode(',', $skuIds);
            $goodsModel = new DBGoods();
            foreach ($spuArr as $key => $value) {
                $id = \DB::table('goods_sku')->where('goods_id', $value)->update(['is_delete' => 1]);
                $spu = \DB::table('goods_sku')->where('goods_id', $value)->select('spu_id')->first();
                //同一商品最高最低价
                $goodsModel->changeMinandMax($spu->spu_id);
                //修改总库存
                $stockModel = new DBStock();
                $stockModel->changeStock($spu->spu_id);
            }
            $msg['data'] = $id;
            $msg['success'] = true;
            return $msg;
        }
    }
}