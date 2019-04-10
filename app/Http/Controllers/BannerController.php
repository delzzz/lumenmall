<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * @param Request $request
     * @return string
     * 查询所有banner
     */
    public function selectAll(Request $request){
        $bannerList = \DB::table('banner')->orderBy('is_top','desc')->orderBy('update_time','desc')->get();
        return json_encode($bannerList, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * @param $bannerId
     * @return string
     * 查询banner详情
     */
    public function selectOne(Request $request,$bannerId){
        if($bannerId==null || $bannerId == ''){
            return '缺少banner_id';
        }
        $banner = \DB::table('banner')->where('banner_id',$bannerId)->get();
        return json_encode($banner, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 添加/编辑广告位
     */
    public function add(Request $request){
        if ($request->isMethod('post')) {
            $bannerId = $request->input('banner_id');
            $this->validate($request, [
                'banner_name' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'image_path' => 'required',
                'banner_url' => 'required',
            ]);
            if($bannerId=='' || empty($bannerId)){
                //添加
                $insertId = \DB::table('banner')->insertGetId(
                    [
                        "banner_name" => $request->input('banner_name'),
                        "start_time" => $request->input('start_time'),
                        "end_time" => $request->input('end_time'),
                        'image_path' => $request->input('image_path'),
                        'banner_url' => $request->input('banner_url'),
                        'remark' => $request->input('remark'),
                    ]
                );
                $msg['data'] = $insertId;

            }
            else{
                //编辑
                $updateId = \DB::table('banner')->where('banner_id',$bannerId)->update(
                    [
                        "banner_name" => $request->input('banner_name'),
                        "start_time" => $request->input('start_time'),
                        "end_time" => $request->input('end_time'),
                        'image_path' => $request->input('image_path'),
                        'banner_url' => $request->input('banner_url'),
                        'remark' => $request->input('remark'),
                    ]
                );
                $msg['data'] = $updateId;
            }
            $msg['success'] = true;
            return $msg;
        }
    }

    /**
     * 删除banner
     */
    public function delete(Request $request,$bannerId){
        if($bannerId==null || $bannerId == ''){
            return '缺少banner_id';
        }
        $id =  \DB::table('banner')->where('banner_id',$bannerId)->delete();
        $msg['data'] = $id;
        $msg['success'] = true;
        return $msg;
    }

    /**
     * 点击增加次数
     */
    public function addClick(Request $request){
        if ($request->isMethod('post')) {
            $bannerId = $request->input('banner_id');
            $this->validate($request, [
                'banner_id' => 'required',
            ]);
            $click = \DB::table('banner')->value('click');
            $updateId = \DB::table('banner')->where('banner_id',$bannerId)->update(
                [
                    "click" => $click+1
                ]
            );
            $msg['data'] = $updateId;
            $msg['success'] = true;
            return $msg;
        }
    }

    /**
     * 置顶banner
     */
    public function setTop(Request $request){
        if ($request->isMethod('post')) {
            $bannerId = $request->input('banner_id');
            $this->validate($request, [
                'banner_id' => 'required',
            ]);
            \DB::table('banner')->update(
                [
                    "is_top" => 0,
                ]
            );
            $updateId = \DB::table('banner')->where('banner_id',$bannerId)->update(
                [
                    "is_top" => 1,
                    'update_time'=>time()
                ]
            );
            $msg['data'] = $updateId;
            $msg['success'] = true;
            return $msg;
        }
    }
}