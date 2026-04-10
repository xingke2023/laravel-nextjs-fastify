<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BaiduMapService;
use Illuminate\Http\Request;

class ScenicApiController extends Controller
{
    public function __construct(private BaiduMapService $service) {}

    /**
     * GET /api/scenic/spots?region=成都
     * 搜索指定地区所有景区（自动翻页）
     */
    public function spots(Request $request)
    {
        $request->validate([
            'region' => 'required|string|min:1',
        ]);

        $region = $request->region;
        $spots  = $this->service->searchByRegion($region);

        return response()->json([
            'success' => true,
            'region'  => $region,
            'total'   => count($spots),
            'data'    => array_map(fn($s) => $this->service->formatSpot($s), $spots),
        ]);
    }

    /**
     * GET /api/scenic/sub-spots?name=五当召&region=包头市&radius=5000
     * 采集某景区的子景点（Detail children + 周边搜索）
     */
    public function subSpots(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|min:1',
            'region' => 'required|string|min:1',
            'radius' => 'integer|min:100|max:50000',
        ]);

        $name   = $request->name;
        $region = $request->region;
        $radius = (int) ($request->radius ?? 5000);

        $result = $this->service->collectSubSpots($name, $region, $radius);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json([
            'success'  => true,
            'name'     => $name,
            'region'   => $region,
            'radius'   => $radius,
            'main'     => $result['main'],
            'total'    => count($result['subSpots']),
            'subSpots' => $result['subSpots'],
        ]);
    }
}
