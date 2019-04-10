<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class DBStock extends Model{
    //库存查看
    public function getSkuStock($skuId){
        $stock =  \DB::table('goods_sku as sku')
            ->join('goods_spu as spu','spu.spu_id','=','sku.spu_id')
            ->where('sku.goods_id',$skuId)
            ->select('spu.goods_name','sku.spec','spu.goods_sn','sku.price','sku.stock','sku.warning_stock')
            ->get();
        return $stock;
    }

    //添加货品补货
    public function addStock($spuId,$skuId,$warningStock,$addStock){
         \DB::table('goods_sku')->where('goods_id',$skuId)->update(['warning_stock'=>$warningStock]);
         \DB::table('goods_sku')->where('goods_id',$skuId)->increment('stock',$addStock);
         \DB::table('goods_spu')->where('spu_id',$spuId)->increment('stock',$addStock);
        return $this->inStock($skuId,$addStock,1,1);
    }

    //入库
    public function inStock($skuId,$addStock,$operateType,$inType,$orderSn = null){
        $goods = \DB::table('goods_sku as sku')
            ->select('sku.*','spu.goods_sn','spu.goods_name')
            ->join('goods_spu as spu','spu.spu_id','=','sku.spu_id')
            ->where('sku.goods_id',$skuId)->first();
        $insertId = \DB::table('goods_stock_in')->insertGetId([
            'goods_id'=>$skuId,
            'goods_sn'=>$goods->goods_sn,
            'goods_name'=>$goods->goods_name,
            'in_num'=>$addStock,
            'current_stock'=>$goods->stock,
            'order_sn'=>$orderSn,
            'operate_type'=>$operateType, //0退货 1添加 2取消订单 3关闭订单
            'in_type'=>$inType, //0商品库存 1货品库存
            'operate_id'=>1, //$userId
            'create_time'=>date('Y-m-d H:i:s'),
        ]);
        $msg['success'] = true;
        $msg['data']=$insertId;
        return $msg;
    }

    //出库  生成订单触发
    public function outStock($skuId,$reduceStock,$operateType,$outType,$orderSn = null){
        \DB::table('goods_sku')->where('goods_id',$skuId)->decrement('stock',$reduceStock);
        $goods = \DB::table('goods_sku as sku')
            ->select('sku.*','spu.goods_sn','spu.goods_name')
            ->join('goods_spu as spu','spu.spu_id','=','sku.spu_id')
            ->where('sku.goods_id',$skuId)->first();
        \DB::table('goods_spu')->where('spu_id',$goods->spu_id)->decrement('stock',$reduceStock);
        return \DB::table('goods_stock_out')->insertGetId([
            'goods_id'=>$skuId,
            'goods_sn'=>$goods->goods_sn,
            'goods_name'=>$goods->goods_name,
            'out_num'=>$reduceStock,
            'current_stock'=>$goods->stock,
            'order_sn'=>$orderSn,
            'operate_type'=>$operateType,
            'out_type'=>$outType,
            'operate_id'=>1, //$userId
            'create_time'=>date('Y-m-d H:i:s'),
        ]);
    }

    //修改sku库存时改变总库存
    public function changeStock($spuId){
        $stock = \DB::table('goods_sku')->where(['spu_id'=>$spuId,'is_delete'=>0])
            ->select(\DB::raw('sum(stock) as total'))
            ->first();
        return \DB::table('goods_spu')->where('spu_id',$spuId)->update(['stock'=>$stock->total]);
    }

}