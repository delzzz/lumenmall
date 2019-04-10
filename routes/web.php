<?php
use Illuminate\Http\Response;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
//$app->get('/', function () {
//    return view('test',['name'=>'aaa']);
//});
//$app->get('/', [
//    'uses' => 'TestController@index'
//]);
//$app->get('user/{id}', function ($id) {
//    return 'User '.$id;
//});

//$app->get('user/profile', [
//    'as' => 'profile', 'uses' => 'UserController@showProfile'
//]);
//$app->get('test',function(){
//    return redirect()->route('profile');
//});

//$app->get('user/store',['uses'=>'UserController@store']);
//$app->get('/', function () use ($app) {
//    return $app->version();
//});
//$app->group(['prefix' => 'admin'], function ($app) {
//    $app->get('users', function ()  {
//        // Matches The "/admin/users" URL
//        return 'lll';
//    });
//});
//
//$app->get('/test',array(
//    'uses'=>'TestController@test',
//));
//$app->get('user/{id}', 'UserController@store');
//$app->post('test','TestController@test');
//
//$app->get('app','TestController@app');
//$app->get('child','TestController@child');
//$app->get('alert','TestController@alert');
//$app->get('test/{id}', 'TestController@test');
//$app->get('inc', 'TestController@alert');

//接口
//$app->get('/','TestController@index');

//管理员登录
$app->post('/adminLogin','PublicController@login');
//获取验证码
$app->get('/getVerifyCode','PublicController@getVerifyCode');
//校验验证码
$app->get('/checkVerifycode','PublicController@checkVerifycode');

//用户注册
$app->post('/userRegister','UserController@register');
//用户登录
$app->post('/userLogin','PublicController@userLogin');
//用户登录次数
$app->post('/userLoginCount','PublicController@userLoginCount');
//忘记密码
$app->post('/forgetPassword','UserController@forgetPassword');

//七牛token
$app->get('/qiniuToken','QiniuController@getToken');


//使用token中间件的接口
//$app->group(['middleware' => 'token'],function ()use($app) {

//模块列表
$app->get('/moduleList','ModuleController@selectAll');
//查询子模块
$app->post('/childModuleList','ModuleController@selectModule');

//角色列表
$app->get('/roleList','RoleController@selectAll');
//获取角色信息
$app->get('/roleDetail/{role_id}','RoleController@selectOne');
//修改角色信息
$app->post('/editRole','RoleController@editRole');
//添加角色
$app->post('/addRole','RoleController@add');
//获取角色权限
$app->get('/roleAccess/{role_id}','RoleController@getAccess');
//编辑角色权限
$app->post('/editAccess','RoleController@editAccess');
//更改角色状态
//$app->post('/changeRoleStatus','RoleController@changeRoleStatus');

//添加编辑后台用户
$app->post('/addAdminUser','AdminuserController@addUser');
//查询管理员列表
$app->post('/adminUserList','AdminuserController@selectAll');
//查询管理员详情
$app->get('/adminUserDetail/{user_id}','AdminuserController@selectOne');
//批量删除管理员
$app->post('/deleteAdminUsers','AdminuserController@delete');
//登录日志
$app->post('/loginRecord','AdminuserController@getLoginRecord');

//检测管理员是否登录
//$app->get('/isAdminLogin','PublicController@isAdminLogin');
//管理员登出
$app->get('/adminLogout','PublicController@adminLogout');



//检测用户是否登录
//$app->get('/isLogin','PublicController@isLogin');
//用户登出
$app->get('/userLogout','PublicController@userLogout');
//查询用户列表
$app->post('/userList','UserController@selectAll');
//查询用户详情
$app->get('/userDetail/{user_id}','UserController@selectOne');
//用户启用禁用
$app->post('/setUserStatus','UserController@setUserStatus');
//用户地址列表
$app->get('/userAddressList/{user_id}','UserController@addressList');
//收货地址详情
$app->get('/userAddressDetail/{address_id}','UserController@addressDetail');
//用户添加修改地址
$app->post('/addAddress','UserController@addAddress');
//设置用户地址
$app->post('/setDefaultAddress','UserController@setDefaultAddress');
//删除用户地址
$app->get('/deleteAddress/{address_id}','UserController@deleteAddress');
//用户头像昵称手机号
$app->get('/getUserBasic/{user_id}','UserController@getUserBasic');
//修改头像
$app->post('/changeAvatar','UserController@changeAvatar');
//用户个人资料
$app->get('/getUserInfo/{user_id}','UserController@getUserInfo');
//修改个人资料
$app->post('/editUserInfo','UserController@editUserInfo');
//修改密码
$app->post('/editPassword','UserController@editPassword');
//点赞商品
    $app->post('/followGoods', 'UserController@followGoods');

//添加购物车
$app->post('/addCart','CartController@add');
//购物车列表
$app->get('/cartList/{user_Id}','CartController@selectAll');
//购物车商品数量更改
$app->post('/changeGoodsNum','CartController@changeGoodsNum');
//购物车删除
$app->post('/deleteCart','CartController@deleteCart');
//确认商品信息
$app->post('/checkCart','CartController@checkCart');
//购物车商品总数
$app->post('/cartTotal','CartController@cartTotal');


//新增/修改运费模板
$app->post('/addShipping','ShippingController@add');
//查询所有运费模板
$app->post('/shippingList','ShippingController@selectAll');
//修改运费模板状态
$app->post('/shippingStatus','ShippingController@changeStatus');
//删除运费模板状态
$app->post('/deleteShipping','ShippingController@delete');
//模板详情
$app->get('/shippingDetail/{shipping_id}','ShippingController@selectOne');
//根据商品物流模板计算物流信息
$app->post('/shippingInfo','ShippingController@getShippingInfo');
//查询快递公司
$app->get('/getExpress','ShippingController@selectExpress');
//物流推送
$app->post('/push_express','ShippingController@pushExpress');


//新增/修改banner
$app->post('/addBanner','BannerController@add');
//查询所有banner
$app->get('/bannerList','BannerController@selectAll');
//添加banner点击次数
$app->post('/bannerClick','BannerController@addClick');
//置顶banner
$app->post('/bannerTop','BannerController@setTop');
//删除banner
$app->get('/deleteBanner/{banner_id}','BannerController@delete');
//banner详情
$app->get('/bannerDetail/{banner_id}','BannerController@selectOne');

//查询地区
$app->post('/regionList','RegionController@selectAll');
//根据region_id查询地区名称
$app->post('/regionName','RegionController@selectOne');

//商品品牌添加修改
$app->post('/addBrand','BrandController@add');
//查询所有品牌
$app->post('/brandList','BrandController@selectAll');
//查询品牌详情
$app->get('/brandDetail/{brand_id}','BrandController@selectOne');
//删除品牌
$app->get('/deleteBrand/{brand_id}','BrandController@deleteOne');

//商品分类列表
$app->get('/categoryList/{category_id}','CategoryController@selectAll');
//商品分类添加
$app->post('/addCategory','CategoryController@add');

//商品添加修改
$app->post('/addGoods','GoodsController@add');
//商品列表
$app->post('/goodsList','GoodsController@selectAll');
//商品详情
$app->get('/goodsDetail/{spu_id}','GoodsController@selectOne');
//SKU库存
$app->get('/skuDetail/{spu_id}','GoodsController@selectSku');
//商品sku修改
$app->post('/editSku','GoodsController@editSku');
//商品批量删除
$app->post('/deleteGoods','GoodsController@delete');
//下架商品
$app->post('/withdrawGoods','GoodsController@withdraw');
//上架商品
$app->post('/putonGoods','GoodsController@puton');
//下架商品列表
$app->post('/withdrawGoodsList','GoodsController@selectWithdraw');
//商品回收站
$app->post('/goodsRecycle','GoodsController@recycleGoods');
//已售罄商品
$app->post('/soldoutGoods','GoodsController@soldoutGoods');
//已售罄商品 sku删除
$app->post('/deleteSku','GoodsController@deleteSku');


//规格列表
$app->get('/specList','SpecController@selectAll');

//查看库存
$app->get('/skuStock/{sku_id}','StockController@skuStock');
//添加库存
$app->post('/addStock','StockController@addStock');
//库存列表
$app->post('/stockList','StockController@selectAll');
//入库列表
$app->post('/stockInList','StockController@stockInList');
//出库列表
$app->post('/stockOutList','StockController@stockOutList');

//优惠列表
$app->post('/promotionList','PromotionController@selectAll');
//优惠详情
$app->get('/promotionDetail/{spu_id}','PromotionController@selectOne');
//添加/修改优惠
$app->post('/addPromotion','PromotionController@add');
//关闭优惠
$app->post('/closePromotion','PromotionController@close');

//推荐列表
$app->post('/recommendationList','RecommendationController@selectAll');
//推荐详情
$app->get('/recommendationDetail/{spu_id}','RecommendationController@selectOne');
//添加/修改推荐
$app->post('/addRecommendation','RecommendationController@add');
//全部关闭推荐
$app->post('/closeAllRecommendation','RecommendationController@closeAll');

//生成订单
$app->post('/createOrder','OrderController@create');
//更改订单状态
$app->post('/changeOrderStatus','OrderController@changeOrderStatus');
//订单列表
$app->post('/orderList','OrderController@selectAll');
//订单详情
$app->get('/orderDetail/{order_id}','OrderController@selectOne');
//关闭订单
$app->post('/closeOrder','OrderController@close');
//取消订单
$app->post('/cancelOrder','OrderController@cancelOrder');
//发货
$app->post('/deliverGoods','OrderController@deliverGoods');
//修改订单
$app->post('/editOrder','OrderController@editOrder');
//自取确定已取货
$app->post('/changePickupStatus','OrderController@changePickupStatus');
//确认收货信息
$app->post('/pickupDetail','OrderController@pickupDetail');
//用户确认收货
$app->post('/changeCollectStatus','OrderController@changeCollectStatus');
//已完成列表
$app->post('/orderDoneList','OrderController@selectAllDone');
//退货列表
$app->post('/orderReturnList','OrderController@returnList');
//申请退货
$app->post('/returnGoods','OrderController@returnGoods');
//同意/拒绝退货(款)列表
$app->post('/operateReturnList','OrderController@operateReturnList');
//操作同意/拒绝退货
$app->post('/operateReturn','OrderController@operateReturn');
//操作同意/拒绝退款
$app->post('/operateRefund','OrderController@operateRefund');
//确认退款信息
$app->post('/confirmRefund','OrderController@confirmRefund');
//物流信息查询
$app->post('/getExpressInfo','ShippingController@getExpressInfo');
//用户订单
$app->post('/userOrder','OrderController@userOrder');
//用户删除订单
$app->post('/deleteOrder','OrderController@deleteOrder');

//交易明细
$app->post('/tradeRecord','TraderecordController@selectAll');
//商家概况
$app->get('/overview','OrderController@overview');

//});
//付款
$app->map(['GET','POST'],'/pay/','PayController@pay');
//支付宝
//支付宝异步通知
$app->post('/alipay_notify','AlipayController@notifyPage');
//支付宝同步通知
$app->get('/alipay_return','AlipayController@returnPage');

//微信
$app->group(['middleware' => 'openid'],function ()use($app){
   $app->get("/test","WechatpayController@index");
});
$app->post('/weixinpay', 'PayController@WeixinPay');
//获取openid
$app->get('/get_openid','WechatpayController@getOpenId');
//微信异步回调
$app->post('/wechat_notify','WechatpayController@notifyPage');

//crontab服务器调用
$app->get('/autoConfirm','PublicController@autoConfirm');








