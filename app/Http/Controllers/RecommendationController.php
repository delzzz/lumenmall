<?php

namespace App\Http\Controllers;
use App\DBPromotion;
use Illuminate\Http\Request;
use App\DBCategory;
class RecommendationController extends Controller
{
    /**
     * 查询所有推荐
     */
    public function selectAll(Request $request){
        if ($request->isMethod('post')) {
            $searchKey = $request->input('search_key');
            $position = $request->input('position');
            $recommendation = \DB::table('recommendation as r')
                ->select('rid',\DB::raw('substring_index(img,",",1) as img'),'goods_name','goods_sn','spu.spu_id','r.start_date','r.end_date','r.position')
                ->join('goods_spu as spu','spu.spu_id','=','r.spu_id')
                ->where('spu.is_off',0)
                ->where('spu.is_delete',0)
                ->where('spu.is_recommend',1)
                ->where('r.status',1)
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('goods_name', 'like', '%' . $searchKey . '%')
                            ->orWhere('goods_sn', 'like', '%' . $searchKey . '%');
                    }})
                    ->where(function ($query) use ($position) {
                        if ($position !== '' && !empty($position)) {
                            $query->where('position', 'like', '%' . $position . '%');
                        }
                })->get();
            return json_encode($recommendation,JSON_UNESCAPED_UNICODE);
        }
    }



    /**
     * @param Request $request
     * @return string添加修改推荐
     */
    public function add(Request $request){
        if ($request->isMethod('post')) {
            $spuId = $request->input('spu_id');
            $position = $request->input('position');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $this->validate($request, [
                'spu_id' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'position' => 'required'
            ]);
            if($spuId>0){
                \DB::table('recommendation')->where('spu_id',$spuId)->delete();
                $positionArr = explode(',',$position);
                //foreach ($positionArr as $key => $value){
                    \DB::table('recommendation')->insert([
                        'spu_id' => $spuId,//$spuId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'position' => $position//$value
                    ]);
               // }
                $promotionModel = new DBPromotion();
                $id = $promotionModel->changeRecommendStatus($spuId,1);
                $msg['success'] = true;
                $msg['data'] = $id;
                return $msg;
            }
            else{
                return 'spu_id不能为0';
            }
        }
    }

    /**
     * 全部关闭推荐
     */
    public function closeAll(Request $request){
        $spuId = $request->input('spu_id');
        $this->validate($request, [
            'spu_id' => 'required',
        ]);
        if($spuId>0){
            \DB::table('recommendation')->where('spu_id',$spuId)->update([
                'status'=>0,
            ]);
            $promotionModel = new DBPromotion();
            $id = $promotionModel->changeRecommendStatus($spuId,0);
            $msg['success'] = true;
            $msg['data'] = $id;
            return $msg;
        }
        else{
            return 'spu_id不能为0';
        }
    }


    /**
     * @param Request $request
     * @param $spuId
     * @return string 推荐详情
     */
    public function selectOne(Request $request,$rid){
        if(empty($rid) || $rid == ''){
            return 'recommendation_id为空';
        }
        $promotionDetail = \DB::table('recommendation')->where('rid',$rid)->first();
        return json_encode($promotionDetail,JSON_UNESCAPED_UNICODE);
    }

}