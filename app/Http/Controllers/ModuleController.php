<?php

namespace App\Http\Controllers;
use App\Repositories\ModuleRepositoryInterface;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
//    protected $module;
//
//    public function __construct(ModuleRepositoryInterface $module)
//    {
//        $this->module = $module;
//    }

    /**
     * @return string
     * 模块列表
     */
    public function selectAll(){
        $moduleList = \DB::table('module')->get();
        return json_encode($moduleList,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 根据parent_id查询子模块
     */
    public function selectModule(Request $request){
        if ($request->isMethod('post')) {
        $module_id = $request->input('module_id');
        $module_id = $module_id==''?0:$module_id;
        $moduleList = \DB::table('module')->where('parent_id',$module_id)->get();
        return json_encode($moduleList,JSON_UNESCAPED_UNICODE);
        }
    }

}