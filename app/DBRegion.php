<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class DBRegion extends Model
{
    //根据region_id查询地区名称
    public function getRegionName($regionId)
    {
        $regionArr = explode(',', $regionId);
        $regionStr = '';
        foreach ($regionArr as $key => $value) {
            $regionName = \DB::table('region')
                ->where('region_id', $value)
                ->value('name');
            //echo $regionName;
            $regionStr .= $regionName . ',';
        }
        return substr($regionStr, 0, strlen($regionStr) - 1);
    }

    //拼接省市区
    public function getFullRegionName($province, $city, $county)
    {
        $region = \DB::table('region')->where('region_id', $province)->first();
        $regionName = '';
        if ($region !== '' && !empty($region)) {
            if ($region->is_direct !== 1) {
                //其他省份
                $regionName .= $region->name;
            }
            $regionName .= $this->getRegionName($city) . $this->getRegionName($county);
            return $regionName;
        }
    }
}