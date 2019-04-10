<?php

namespace App\Http\Controllers;
use App\DBPromotion;
use Illuminate\Http\Request;
use App\DBCategory;
class PromotionController extends Controller
{
    /**
     * 查询所有优惠
     */
    public function selectAll(Request $request){
        if ($request->isMethod('post')) {
            $searchKey = $request->input('search_key');
            $promotionList = \DB::table('promotion as p')
                ->select('p.promotion_id',\DB::raw('substring_index(spu.img,",",1) as img'),'spu.goods_name','spu.goods_sn','spu.l_price as min_price','spu.stock',
                    'p.spu_id','p.start_date','p.end_date','p.sales_price','p.sales_num')
                ->join('goods_spu as spu','p.spu_id','=','spu.spu_id')
                ->where('p.status',1)
                ->where('spu.is_off',0)
                ->where('spu.is_delete',0)
                ->where(function ($query) use ($searchKey) {
                    if ($searchKey !== '' || !empty($searchKey)) {
                        $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                            ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                    }
                })
                ->get();
            return json_encode($promotionList,JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param Request $request
     * @return string添加修改优惠
     */
    public function add(Request $request){
        if ($request->isMethod('post')) {
            $spuId = $request->input('spu_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $salesPrice = $request->input('sales_price');
            $salesNum = $request->input('sales_num');
            $gap = $request->input('gap');
            $this->validate($request, [
                'spu_id' => 'required',
                'start_date' => 'required|before:end_date',
                'end_date' => 'required',
                'sales_price' => 'required',
                'sales_num' => 'required',
                'gap' => 'required'
            ]);
            if($spuId>0){
                $spu = \DB::table('goods_spu')->where('spu_id',$spuId)->select('stock')->first();
                if($salesNum > $spu->stock){
                    return '促销数量大于库存数量';
                }
                $count = \DB::table('promotion')->where(['spu_id'=>$spuId,'status'=>1])->count();
                if($count>0){
                    //修改优惠
                    \DB::table('promotion')->where('spu_id',$spuId)->update([
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'sales_price' => $salesPrice,
                        'sales_num' => $salesNum,
                        'gap'=>$gap,
                        'update_time'=>date('Y-m-d H:i:s')
                    ]);
                }
                else{
                    //添加优惠
                    \DB::table('promotion')->insertGetId([
                        'spu_id' => $spuId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'sales_price' => $salesPrice,
                        'sales_num' => $salesNum,
                        'gap'=>$gap,
                        'create_time'=>date('Y-m-d H:i:s')
                    ]);
                }
                $promotionModel = new DBPromotion();
                $id = $promotionModel->changeSaleStatus($spuId,1);
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
     * 关闭优惠
     */
    public function close(Request $request){
        $spuId = $request->input('spu_id');
        $this->validate($request, [
            'spu_id' => 'required',
        ]);
        if($spuId>0){
            \DB::table('promotion')->where('spu_id',$spuId)->update([
                'status'=>0,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $promotionModel = new DBPromotion();
            $id = $promotionModel->changeSaleStatus($spuId,0);
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
     * @return string 查询优惠详情
     */
    public function selectOne(Request $request,$promotionId){
        if(empty($promotionId) || $promotionId == ''){
            return 'promotion_id为空';
        }
        $promotionDetail = \DB::table('promotion as p')
            ->select('spu.spu_id','p.start_date','p.end_date','spu.l_price as min_price','p.sales_price','spu.stock')
            ->join('goods_spu as spu','spu.spu_id','=','p.spu_id')
            ->where('p.promotion_id',$promotionId)->first();
        return json_encode($promotionDetail,JSON_UNESCAPED_UNICODE);
    }



}