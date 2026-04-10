<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommentaryPoint;
use Illuminate\Http\Request;

class CommentaryPointController extends Controller
{
    /**
     * GET /api/commentary/spot/{spotId}
     * 景区全部讲解点（APP 进景区时一次性下载）
     */
    public function bySpot(int $spotId)
    {
        $points = CommentaryPoint::with('scenicNode:id,name')
            ->where('scenic_spot_id', $spotId)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get([
                'id', 'scenic_node_id', 'title', 'content',
                'audio_url', 'image_url',
                'lat', 'lng', 'trigger_radius', 'duration', 'sort_order',
            ]);

        return response()->json([
            'success' => true,
            'spotId'  => $spotId,
            'total'   => $points->count(),
            'data'    => $points,
        ]);
    }

    /**
     * GET /api/commentary/node/{nodeId}
     * 某景点的讲解点列表
     */
    public function byNode(int $nodeId)
    {
        $points = CommentaryPoint::where('scenic_node_id', $nodeId)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get([
                'id', 'title', 'content', 'audio_url', 'image_url',
                'lat', 'lng', 'trigger_radius', 'duration', 'sort_order',
            ]);

        return response()->json([
            'success' => true,
            'nodeId'  => $nodeId,
            'total'   => $points->count(),
            'data'    => $points,
        ]);
    }

    /**
     * GET /api/commentary/nearby?lat=&lng=&spotId=&radius=
     * GPS 附近讲解点（移动端实时触发）
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'spotId' => 'required|integer',
            'radius' => 'integer|min:10|max:500',
        ]);

        $lat    = (float) $request->lat;
        $lng    = (float) $request->lng;
        $spotId = (int)   $request->spotId;
        $radius = (int)   ($request->radius ?? 50);

        // Haversine 在数据库内计算球面距离，只返回在触发范围内的点
        $points = CommentaryPoint::selectRaw(
            'id, scenic_node_id, title, content, audio_url, image_url,
             lat, lng, trigger_radius, duration, sort_order,
             ROUND(
               6371000 * 2 * ASIN(SQRT(
                 POW(SIN((RADIANS(lat) - RADIANS(?)) / 2), 2) +
                 COS(RADIANS(?)) * COS(RADIANS(lat)) *
                 POW(SIN((RADIANS(lng) - RADIANS(?)) / 2), 2)
               ))
             ) AS distance_m',
            [$lat, $lat, $lng]
        )
            ->where('scenic_spot_id', $spotId)
            ->where('is_published', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->havingRaw('distance_m <= GREATEST(trigger_radius, ?)', [$radius])
            ->orderBy('distance_m')
            ->get();

        return response()->json([
            'success' => true,
            'userLat' => $lat,
            'userLng' => $lng,
            'spotId'  => $spotId,
            'radius'  => $radius,
            'total'   => $points->count(),
            'data'    => $points,
        ]);
    }
}
