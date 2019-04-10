<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DBGoods;

class BrandController extends Controller
{
    /**
     * 查询所有品牌
     */
    public function selectAll(Request $request){
        $brandName = $request->input('brand_name');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $brandList = \DB::table('brand')
            ->select('brand.brand_id','brand.brand_logo','brand_description','brand.brand_url','brand.create_time')
            ->where(function($query) use($brandName) {
                if($brandName!=''|| !empty($brandName)){
                    $query->where('brand_name','like','%'.$brandName.'%');
                }})
            ->when(true,function($query) use($page,$perPage){
                if($perPage!=''|| !empty($perPage)){
                    $query->offset(($page-1)*$perPage);
                }})
            ->when(true,function($query) use($perPage) {
                if($perPage!=''|| !empty($perPage)){
                    $query->limit($perPage);
                }})
            ->where('is_delete',0)
            ->orderBy('brand_id','asc')
            ->get();
        //品牌商品数量
        $brandList = json_decode($brandList,true);
        $goodsModel = new DBGoods();
        foreach ($brandList as $key=>&$value){
            foreach ($value as $k => $v){
                if($k=='brand_id'){
                    $value['total'] = $goodsModel->getGoodsTotal($v);
                }
            }
        }
        return json_encode($brandList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取某品牌
     */
    public function selectOne(Request $request,$brandId){
    $moduleList = \DB::table('brand')->where('brand_id',$brandId)->get();
    return json_encode($moduleList, JSON_UNESCAPED_UNICODE);
}

    /**
     * 商品品牌添加/修改
     */
    public function add(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'brand_name' => 'required',
                'brand_logo' => 'required',
                'brand_description' => 'required',
                'brand_url' => 'required',
            ]);
            $brand_id = $request->input('brand_id');
            if ($brand_id == '' || empty($brand_id)) {
                $insertId = \DB::table('brand')->insertGetId(
                    [
                        "brand_name" => $request->input('brand_name'),
                        'brand_logo' => $request->input('brand_logo'),
                        'brand_description' => $request->input('brand_description'),
                        'brand_url' => $request->input('brand_url'),
                        'create_time' => date('Y-m-d H:i:s')
                    ]
                );
                $msg['data'] = $insertId;
            }
            else {
                //修改品牌
                $updateId = \DB::table('brand')->where('brand_id',$brand_id)->update(
                    [
                        "brand_name" => $request->input('brand_name'),
                        'brand_logo' => $request->input('brand_logo'),
                        'brand_description' => $request->input('brand_description'),
                        'brand_url' => $request->input('brand_url'),
                    ]
                );
                $msg['data'] = $updateId;
            }
            $msg['success'] = true;
            return $msg;
        }
    }
    /**
     * 删除品牌
     */
    public function deleteOne(Request $request,$brand_id=null){
        if($brand_id == null || $brand_id == ''){
            return '缺少brand_id';
        }
        //有商品的品牌不能删除判断
        $goodsModel = new DBGoods();
        $total = $goodsModel->getGoodsTotal($brand_id);
       // return $total;
        if($total > 0){
            $msg['data'] = -1;
            $msg['success'] = false;
        }
        else{
            $updateId = \DB::table('brand')->where('brand_id',$brand_id)->update(['is_delete'=>1]);
            $msg['data'] = $updateId;
            $msg['success'] = true;
        }
        return $msg;
    }

}