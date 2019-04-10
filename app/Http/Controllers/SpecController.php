<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\DBGoodsSpec;
class SpecController extends Controller
{
    //查询所有规格
    public function selectAll(Request $request){
        $specModel = new DBGoodsSpec();
        $specList = $specModel->getSpecList();
        return json_encode($specList,JSON_UNESCAPED_UNICODE);
    }



}