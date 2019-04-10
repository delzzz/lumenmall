<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class DBPromotion extends Model{
    //开启优惠 spu表 is_sale 1开启 0关闭
    public function changeSaleStatus($spuId,$status){
        $id = \DB::table('goods_spu')->where('spu_id',$spuId)->update(['is_sale'=>$status]);
        return $id;
    }

    //开启详情 spu表 is_recommend 1开启 0关闭
    public function changeRecommendStatus($spuId,$status){
        $id = \DB::table('goods_spu')->where('spu_id',$spuId)->update(['is_recommend'=>$status]);
        return $id;
    }

    //自动关闭过期优惠
    public function turnOffPromotion(){
        $currentDate = date('Y-m-d');
        $offPromotionId = \DB::table('promotion')->where('status',1)->where('end_date','<',$currentDate)->pluck('promotion_id');
        $offSpuId = \DB::table('promotion')->where('status',1)->where('end_date','<',$currentDate)->pluck('spu_id');
        \DB::table('promotion')->whereIn('promotion_id',$offPromotionId)->update(['status'=>0,'update_time'=>date('Y-m-d H:i:s')]);
        \DB::table('goods_spu')->whereIn('spu_id',$offSpuId)->update(['is_sale'=>0]);
    }

    //自动关闭推荐位
    public function turnOffRecommend(){
        $currentDate = date('Y-m-d');
        $offRecommendId = \DB::table('recommendation')->where('status',1)->where('end_date','<',$currentDate)->pluck('rid');
        $offSpuId = \DB::table('recommendation')->where('status',1)->where('end_date','<',$currentDate)->pluck('spu_id');
        \DB::table('recommendation')->whereIn('rid',$offRecommendId)->update(['status'=>0]);
        \DB::table('goods_spu')->whereIn('spu_id',$offSpuId)->update(['is_recommend'=>0]);
    }
}