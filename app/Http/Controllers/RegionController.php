<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\DBRegion;
class RegionController extends Controller
{
    //根据parent_id查询地区列表
    public function selectAll(Request $request){
        if ($request->isMethod('post')) {
            $parentId = $request->input('parent_id');
            $searchKey = $request->input('search_key');
            $isDirect = $request->input('is_direct');
            $regionList = \DB::table('region')
                ->where(function($query) use($parentId) {
                    if($parentId!=''|| !empty($parentId)){
                        $query->where('parent_id',$parentId);
                    }
                })
                ->where(function($query) use($searchKey) {
                    if($searchKey!=''|| !empty($searchKey)){
                        $query->where('name','like','%'.$searchKey.'%');
                    }
                })
                ->where(function($query) use($isDirect) {
                    if($isDirect!='' ||  !empty($isDirect)){
                        $query->where('is_direct',$isDirect);
                    }
                })
                ->select('region_id','parent_id','name','level')
                ->get();
            return json_encode($regionList,JSON_UNESCAPED_UNICODE);
        }
    }

    //根据region_id查询地区名称
    public function selectOne(Request $request){
        if ($request->isMethod('post')) {
            $regionId = $request->input('region_id');
            $regionModel = new DBRegion();
            return $regionModel->getRegionName($regionId);
        }
    }

}