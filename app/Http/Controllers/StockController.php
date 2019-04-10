<?php

namespace App\Http\Controllers;

use App\DBStock;
use App\Repositories\ModuleRepositoryInterface;
use Illuminate\Http\Request;

class StockController extends Controller
{
    //库存查看
    public function skuStock(Request $request, $skuId)
    {
        if ($skuId == '' || empty($skuId)) {
            return '缺少sku_id';
        }
        $stockModel = new DBStock();
        $stock = $stockModel->getSkuStock($skuId);
        return json_decode($stock, JSON_UNESCAPED_UNICODE);
    }

    //补货
    public function addStock(Request $request)
    {
        $this->validate($request, [
            'spu_id' => 'required',
            'goods_id' => 'required',
            'add_stock' => 'required',
            'warning_stock' => 'required',
        ]);
        $stockModel = new DBStock();
        return $stockModel->addStock($request->input('spu_id'), $request->input('goods_id'), $request->input('warning_stock'), $request->input('add_stock'));
    }

    //库存列表
    public function selectAll(Request $request)
    {
        $status = $request->input('status');
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $totalCount = \DB::table('goods_sku as sku')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->where('sku.is_delete', 0)
            ->where(function ($query) use ($status) {
                if (!empty($status) && $status != '') {
                    if ($status == 2) {
                        //下架
                        $query->where('spu.is_off', 1);
                    } elseif ($status == 3) {
                        //快售罄
                        $query->where('sku.stock', '>', 0)->where(\DB::raw('CAST(sku.stock AS SIGNED INTEGER)'), '<=', \DB::raw('CAST(sku.warning_stock AS SIGNED INTEGER)'));

                    } elseif ($status == 4) {
                        //已售罄
                        $query->where('sku.stock', '=', 0);
                    }
                }
            })
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->count();
        $stock = \DB::table('goods_sku as sku')
            ->select(\DB::raw('substring_index(spu.img,",",1) as img'), 'sku.goods_id', 'sku.goods_img', 'spu.spu_id', 'spu.goods_name', 'spu.goods_sn', 'sku.sku_sn', 'sku.spec',
                 'sku.price', 'sku.warning_stock', 'sku.stock', 'spu.is_off')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->leftJoin('order_goods as order', 'order.goods_id', '=', 'sku.goods_id')
            ->groupBy('sku.goods_id')
            ->where('sku.is_delete', 0)
            ->where(function ($query) use ($status) {
                if (!empty($status) && $status != '') {
                    if ($status == 2) {
                        //下架
                        $query->where('spu.is_off', 1);
                    } elseif ($status == 3) {
                        //快售罄
                        $query->where('sku.stock', '>', 0)->where(\DB::raw('CAST(sku.stock AS SIGNED INTEGER)'), '<=', \DB::raw('CAST(sku.warning_stock AS SIGNED INTEGER)'));

                    } elseif ($status == 4) {
                        //已售罄
                        $query->where('sku.stock', '=', 0);
                    }
                }
            })
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->when(true, function ($query) use ($page, $perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->offset(($page - 1) * $perPage);
                }
            })
            ->when(true, function ($query) use ($perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->limit($perPage);
                }
            })
            ->get();

        foreach ($stock as $key => $value) {
            //计算销量
            $order = \DB::table('order_goods')
                ->select(\DB::raw('sum(order_goods.goods_num) as sales_total'))
                ->join('order', 'order.order_id', 'order_goods.order_id')
                ->where(['goods_id' => $value->goods_id, 'order.pay_status' => 1])
                ->first();
            $value->sales_total = $order->sales_total??0;
            if ($stock[$key]->is_off == 1) {
                $stock[$key]->status = '已下架';
            } else {
                if ($stock[$key]->stock == 0) {
                    $stock[$key]->status = '已售罄';
                } elseif ($stock[$key]->stock <= $stock[$key]->warning_stock) {
                    $stock[$key]->status = '快售罄';
                } else {
                    $stock[$key]->status = '在售';
                }
            }
        }
        $stockList['totalCount'] = $totalCount;
        $stockList['itemList'] = $stock;
        return $stockList;
    }

    //入库列表
    public function stockInList(Request $request)
    {
        $type = $request->input('type');
        $sort = $request->input('sort');
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $sortField = 'in_id';
        $sortOrder = 'asc';
        $sevenDays = date('Y-m-d', strtotime('-6 days'));
        $thirtyDays = date('Y-m-d', strtotime('-30 days'));
        if ($sort !== '' || !empty($sort)) {
            if ($sort == 1 || $sort == 3 || $sort == 4) {
                //时间近到远
                $sortField = 'create_time';
                $sortOrder = 'desc';
            } elseif ($sort == 2) {
                //时间远到近
                $sortField = 'create_time';
                $sortOrder = 'asc';
            }
        }
        $totalCount = \DB::table('goods_stock_in as stockin')
            ->join('goods_sku as sku', 'sku.goods_id', '=', 'stockin.goods_id')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->where(function ($query) use ($type) {
                if ($type !== '' || !empty($type)) {
                    if ($type == 1) {
                        //添加商品
                        $query->where('operate_type', 1);
                    } elseif ($type == 2) {
                        //退货
                        $query->where('operate_type', 0);
                    }
                }
            })
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($sort, $sevenDays, $thirtyDays) {
                if ($sort !== '' || !empty($sort)) {
                    if ($sort == 3) {
                        $query->where('stockin.create_time', '>', $thirtyDays);
                    } elseif ($sort == 4) {
                        $query->where('stockin.create_time', '>', $sevenDays);
                    }
                }
            })
            ->count();
        $stockIn = \DB::table('goods_stock_in as stockin')
            ->select(\DB::raw('substring_index(spu.img,",",1) as img'), 'stockin.*', 'sku.goods_img', 'spu.goods_name', 'spu.goods_sn', 'sku.sku_sn', 'sku.spec', 'user.user_name')
            ->join('goods_sku as sku', 'sku.goods_id', '=', 'stockin.goods_id')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->join('admin_user as user', 'user.user_id', '=', 'operate_id')
            ->where(function ($query) use ($type) {
                if ($type !== '' || !empty($type)) {
                    if ($type == 1) {
                        //添加商品
                        $query->where('operate_type', 1);
                    } elseif ($type == 2) {
                        //退货
                        $query->where('operate_type', 0);
                    } elseif ($type == 3) {
                        //取消订单
                        $query->where('operate_type', 2);
                    } elseif ($type == 4) {
                        //退货
                        $query->where('operate_type', 3);
                    }
                }
            })
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($sort, $sevenDays, $thirtyDays) {
                if ($sort !== '' || !empty($sort)) {
                    if ($sort == 3) {
                        $query->where('stockin.create_time', '>', $thirtyDays);
                    } elseif ($sort == 4) {
                        $query->where('stockin.create_time', '>', $sevenDays);
                    }
                }
            })
            ->when(true, function ($query) use ($page, $perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->offset(($page - 1) * $perPage);
                }
            })
            ->when(true, function ($query) use ($perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->limit($perPage);
                }
            })
            ->orderBy($sortField, $sortOrder)
            ->get();
        $stockInList['totalCount'] = $totalCount;
        $stockInList['itemList'] = $stockIn;
        return $stockInList;
    }

    //出库列表
    public function stockOutList(Request $request)
    {
        $type = $request->input('type');
        $sort = $request->input('sort');
        $searchKey = $request->input('search_key');
        $page = $request->input('page');
        $perPage = $request->input('perPage');
        $sortField = 'out_id';
        $sortOrder = 'asc';
        $sevenDays = date('Y-m-d', strtotime('-6 days'));
        $thirtyDays = date('Y-m-d H:i:s', strtotime('-29 days'));
        if ($sort !== '' || !empty($sort)) {
            if ($sort == 1 || $sort == 3 || $sort == 4) {
                //时间近到远
                $sortField = 'create_time';
                $sortOrder = 'desc';
            } elseif ($sort == 2) {
                //时间远到近
                $sortField = 'create_time';
                $sortOrder = 'asc';
            }
        }
        $totalCount = \DB::table('goods_stock_out as stockout')
            ->join('goods_sku as sku', 'sku.goods_id', '=', 'stockout.goods_id')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->join('admin_user as user', 'user.user_id', '=', 'operate_id')
            ->where(function ($query) use ($type) {
                if ($type !== '' || !empty($type)) {
                    if ($type == 1) {
                        //订单提交
                        $query->where('operate_type', 0);
                    }
                        // elseif ($type == 2) {
//                        //二次发货
//                        $query->where('operate_type', 1);
//                    }
                }
            })
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($sort, $sevenDays, $thirtyDays) {
                if ($sort !== '' || !empty($sort)) {
                    if ($sort == 3) {
                        $query->where('stockout.create_time', '>', $thirtyDays);
                    } elseif ($sort == 4) {
                        $query->where('stockout.create_time', '>', $sevenDays);
                    }
                }
            })
            ->count();
        $stockOut = \DB::table('goods_stock_out as stockout')
            ->select(\DB::raw('substring_index(spu.img,",",1) as img'), 'stockout.*', 'sku.goods_img', 'spu.goods_name', 'spu.goods_sn', 'sku.sku_sn', 'sku.spec', 'user.user_name')
            ->join('goods_sku as sku', 'sku.goods_id', '=', 'stockout.goods_id')
            ->join('goods_spu as spu', 'spu.spu_id', '=', 'sku.spu_id')
            ->join('admin_user as user', 'user.user_id', '=', 'operate_id')
            ->where(function ($query) use ($type) {
                if ($type !== '' || !empty($type)) {
                    if ($type == 1) {
                        //订单提交
                        $query->where('operate_type', 0);
                    } elseif ($type == 2) {
                        //二次发货
                        $query->where('operate_type', 1);
                    }
                }
            })
            ->where(function ($query) use ($searchKey) {
                if ($searchKey !== '' || !empty($searchKey)) {
                    $query->where('spu.goods_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('spu.goods_sn', 'like', '%' . $searchKey . '%');
                }
            })
            ->where(function ($query) use ($sort, $sevenDays, $thirtyDays) {
                if ($sort !== '' || !empty($sort)) {
                    if ($sort == 3) {
                        $query->where('stockout.create_time', '>', $thirtyDays);
                    } elseif ($sort == 4) {
                        $query->where('stockout.create_time', '>', $sevenDays);
                    }
                }
            })
            ->when(true, function ($query) use ($page, $perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->offset(($page - 1) * $perPage);
                }
            })
            ->when(true, function ($query) use ($perPage) {
                if ($perPage != '' || !empty($perPage)) {
                    $query->limit($perPage);
                }
            })
            ->orderBy($sortField, $sortOrder)
            ->get();
        $stockOutList['totalCount'] = $totalCount;
        $stockOutList['itemList'] = $stockOut;
        return $stockOutList;
    }
}