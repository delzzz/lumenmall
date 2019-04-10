<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

Class DBGoodsSpec extends Model{
    //查询所有规格项
    public function getSpecList(){
        return \DB::table('spec')->get();
    }

    //查询规格名
    public function getSpecName($specId){
        $res =  \DB::table('spec')->where('spec_id',$specId)->select('spec_name')->get();
        return $res[0];
    }

    //查询某spu下规格
    public function selectSpuSpecs($spuId){
        $res =  \DB::table('spec_info')->where('spu_id',$spuId)->select('spec_id,spec_name')->get();
        return $res;
    }


}