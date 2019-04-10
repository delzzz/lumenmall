<?php

namespace App\Http\Controllers;

use App\User;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Users;
use App\func;

class TestController extends BaseController
{
    public function index()
    {
       // $_GET["out_trade_no"]
        return view('index');
    }

    public function test(Request $request)
    {
       // return view('Wechat.index');
//        testtest();
//        if ($request->isMethod('post')) {
//            $name = $request->input('name');
//            echo $name;
//            echo $request->method();
//            echo $request->url();
//            echo $request->path();
//            $input = $request->all();
//            print_r($input);
//        }
//        echo $id;
        //增删改查curd
        //\DB::select('select * from users where user_id=?',[1]);
        //\DB::update('update users set nickname = ? where user_id = ?',['aaa',1]);
        //\DB::delete('delete from users where user_id=?',[1]);

        //查询构造器
        //$res = \DB::table('users')->get();
        //insertGetId
        //\DB::table('user')->insert(['user_name'=>'cc','nickname'=>'c']);
        //\DB::table('user')->where('user_id',3)->update(['nickname'=>'ccc']);
        $results = \DB::table('user')->select('user_id','user_name')->get();
        //$results = \App\User::all();
        $res = \App\GoodsAttr::all();
        var_dump($results);
        //$results = \DB::table('user')->where('user_id','<>',3)->orderBy('user_id','desc')->first();
        //$results = \DB::table('user')->whereRaw('user_id > ? and user_name !=?',[1,'cc'])->get();
        //列值
        //$results = \DB::table('user')->pluck('nickname');
        //结果分块
        //\DB::table('user')->orderBy('user_id')->chunk(2, function ($users) {
        //       var_dump($users);
        //});
        // dd($results);
        //$results = User::all();
        //$results = Users::find(9);
        //$results = Users::where('user_id','>','1')->orderBy('user_id','desc')->first();
        //  $results = Users::count();

        //$Users = new Users();
        //echo  date('Y-m-d H:i:s');
        //echo time();
        echo date('Y-m-d H:i:s',time());
        //$User = Users::find(2);
        //echo time();
       // $User->create_time = date('Y-m-d H:i:s');
        //$Users->nickname = 'x';
        //echo $User->create_time;
        //$results = $User->save();

        //$User = Users::find(1);
       // echo $User->create_time;

       // dd($results);
        return view('test', ['res' => $results]);
    }

    public function app(Request $request)
    {

        return view('layouts.app');
    }

    public function child()
    {
        return view('child');
    }

    public function alert()
    {
        return view('inc.alert', ['user' => '111']);
    }

}
