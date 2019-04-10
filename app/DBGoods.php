<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class DBGoods extends Model
{
    //同一品牌下商品数
    public function getGoodsTotal($brandId)
    {
        return \DB::table('goods_spu')->where('brand_id', $brandId)->count();
    }

    //根据spu_id查询sku
    public function selectSku($spuId)
    {
        $skuList = \DB::table('goods_sku')
            ->where('spu_id', $spuId)
            ->where('is_delete', 0)
            ->where('stock', '>', 0)
            ->get();
        $skuList = json_decode($skuList, true);
        foreach ($skuList as $key => &$value) {
            $spec = json_decode($value['spec']);
            empty($spec) ? $spec = array() : $spec;
            $specStr = '';
            foreach ($spec as $k => &$v) {
                $specStr .= $v->name.':'.$v->value.';';
                foreach ($v as $skey => $svalue) {
                    if ($skey == 'name') {
                        $value['keyname'][] = $svalue;
                    }
                    if ($skey == 'value') {
                        $value['keyvalue'][] = $svalue;
                    }
                }
            }
            $value['specStr'] = substr($specStr,0,strlen($specStr)-1);
        }
        return $skuList;
    }

    //修改最高最低价
    public function changeMinandMax($spuId){
        //同一商品最高最低价
        $minPrice = \DB::table('goods_sku')->where('spu_id', $spuId)->where('is_delete', 0)->min('price');
        $maxPrice = \DB::table('goods_sku')->where('spu_id', $spuId)->where('is_delete', 0)->max('price');
        $updateId = \DB::table('goods_spu')->where('spu_id', $spuId)->update([
            'l_price' => $minPrice,
            'h_price' => $maxPrice
        ]);
        return $updateId;
    }

    //过了开售结束时间自动下架
    public function turnOffGoods(){
        $currentTime = date('Y-m-d');
        $offSpu = \DB::table('goods_spu')->where(['is_delete'=>0,'is_off'=>0])
            ->where('set_time_sell',1)->where('end_time','<',$currentTime)
            ->pluck('spu_id');
        \DB::table('goods_spu')->whereIn('spu_id',$offSpu)->update(['is_off'=>1,'update_time'=>$currentTime]);
    }

    //根据spu_id查询sku总条数
//    public function getSkuTotal($spuId){
//        return \DB::table('goods_sku as sku')
//            ->select(\DB::raw('count(*) as total'))
//            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
//            ->where('sku.spu_id', $spuId)
//            ->groupBy('sku.spu_id')
//            ->first();
//    }

    //判断sku列是否全删除，是则spu删除
//    public function deleteSpu(){
//        $skuList = \DB::table('goods_sku')
//            ->select('spu_id', \DB::raw('count(*) as total'))
//            ->where('is_delete', 1)
//            ->groupBy('spu_id')
//            ->get();
//        foreach ($skuList as $key => $value) {
//            $spuId = $skuList[$key]->spu_id;
//            $skuDelTotal = $skuList[$key]->total;
//            $skuTotalList = $this->getSkuTotal($spuId);
//            $skuTotal = $skuTotalList->total;
//            if($skuDelTotal == $skuTotal){
//                //sku条目全删除，spu删除
//               return \DB::table('goods_spu')->where('spu_id',$spuId)->update(['is_delete'=>1,'delete_time'=>date('Y-m-d H:i:s')]);
//            }
//        }
//    }

}