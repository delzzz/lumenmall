<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\DBCategory;
class CategoryController extends Controller
{
    /**
     * 查询所有分类
     */
    public function selectAll(Request $request,$pid){
            $categoryModel = new DBCategory();
            $categoryList = $categoryModel->getAllCategory($pid);
            return json_encode($categoryList,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 新增分类
     */
    public function add(Request $request){
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'category_name' => 'required',
                'pid' => 'required',
            ]);
            $categoryName = $request->input('category_name');
            $pid = $request->input('pid');
            $categoryModel = new DBCategory();
            return $categoryModel->addCategory($categoryName,$pid);
        }
    }
}