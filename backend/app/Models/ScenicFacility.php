<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenicFacility extends Model
{
    protected $fillable = [
        'scenic_spot_id', 'type', 'baidu_uid', 'name', 'address',
        'lat', 'lng', 'distance', 'telephone', 'overall_rating',
        'shop_hours', 'price', 'tag',
    ];

    public static array $typeLabels = [
        'parking'       => '停车场',
        'toilet'        => '公共厕所',
        'restaurant'    => '美食餐厅',
        'hotel'         => '酒店住宿',
        'shopping'      => '购物',
        'entertainment' => '休闲娱乐',
        'sports'        => '运动健身',
        'hospital'      => '医疗',
        'bank'          => '银行ATM',
        'transport'     => '交通设施',
    ];

    public static array $typeQueries = [
        'parking'       => '停车场',
        'toilet'        => '公共厕所',
        'restaurant'    => '餐厅$美食$中餐厅$小吃快餐店$咖啡厅$茶座',
        'hotel'         => '酒店$宾馆$民宿$快捷酒店$公寓式酒店',
        'shopping'      => '购物中心$超市$商铺$市场$便利店',
        'entertainment' => '度假村$电影院$休闲广场$游乐园',
        'sports'        => '体育场馆$健身中心$极限运动场所',
        'hospital'      => '医院$诊所$急救$药店$体检机构',
        'bank'          => 'ATM$银行$信用社',
        'transport'     => '火车站$汽车站$公交车站$地铁站',
    ];

    public function scenicSpot(): BelongsTo
    {
        return $this->belongsTo(ScenicSpot::class);
    }
}
