<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class DBCategory extends Model{
    //查询所有分类
    public function getAllCategory($pid){
        return \DB::table('category')->where('pid',$pid)->get();
    }

    //查询一级分类数量
    public function getCategoryCount(){
        return \DB::table('category')->where('pid',0)->count();
    }

    //新增分类
    public function addCategory($categoryName,$pid){
        $insertId = \DB::table('category')->insertGetId(
            [
                "category_name" => $categoryName,
                "pid"=>$pid,
            ]
        );
        $msg['data'] = $insertId;
        $msg['success'] = true;
        return $msg;
    }

}