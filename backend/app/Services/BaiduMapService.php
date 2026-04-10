<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaiduMapService
{
    private string $ak;
    private string $baseUrl = 'https://api.map.baidu.com/place/v3';

    public function __construct()
    {
        $this->ak = config('services.baidu_map.ak', '');
    }

    // ---------------------------------------------------------------
    // 基础请求
    // ---------------------------------------------------------------

    private function get(string $endpoint, array $params): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/{$endpoint}", array_merge($params, [
                'ak'     => $this->ak,
                'output' => 'json',
                'scope'  => 2,
            ]));
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("BaiduMap API error: {$e->getMessage()}");
            return ['status' => -1, 'message' => $e->getMessage()];
        }
    }

    // ---------------------------------------------------------------
    // 按名称搜索景区主点（行政区划检索 v3/region）
    // ---------------------------------------------------------------

    // ---------------------------------------------------------------
    // 按地区搜索所有景区（自动翻页，最多 20 页）
    // ---------------------------------------------------------------

    public function searchByRegion(string $region, int $maxPages = 20): array
    {
        $all  = [];
        $page = 0;

        while ($page < $maxPages) {
            $data = $this->get('region', [
                'query'        => '景区',
                'region'       => $region,
                'region_limit' => 'true',
                'tag'          => '旅游景点',
                'page_size'    => 20,
                'page_num'     => $page,
            ]);

            if (($data['status'] ?? -1) !== 0 || empty($data['results'])) break;

            $all = array_merge($all, $data['results']);

            if (count($data['results']) < 20) break;
            $page++;
            usleep(300000);
        }

        return $all;
    }

    public function searchByName(string $name, string $region = ''): array
    {
        $params = [
            'query'        => $name,
            'page_size'    => 5,
            'page_num'     => 0,
        ];
        if ($region) {
            $params['region']       = $region;
            $params['region_limit'] = 'true';
        }
        return $this->get('region', $params);
    }

    // ---------------------------------------------------------------
    // 地点详情检索（v3/detail）—— 直接返回 children 子点
    // ---------------------------------------------------------------

    public function getDetail(string $uid): array
    {
        return $this->get('detail', ['uid' => $uid]);
    }

    public function getDetailBatch(array $uids): array
    {
        // 最多 10 个 uid 一批
        return $this->get('detail', ['uids' => implode(',', array_slice($uids, 0, 10))]);
    }

    // ---------------------------------------------------------------
    // 圆形周边检索（v3/around）—— 多关键词用 $ 分隔
    // ---------------------------------------------------------------

    public function searchAround(
        float $lat,
        float $lng,
        int $radius,
        string $keywords,
        int $page = 0
    ): array {
        return $this->get('around', [
            'query'        => $keywords,
            'location'     => "{$lat},{$lng}",
            'radius'       => $radius,
            'radius_limit' => 'true',
            'page_size'    => 20,
            'page_num'     => $page,
        ]);
    }

    // ---------------------------------------------------------------
    // 采集景区子景点（主流程）
    //
    // 策略：
    //   1. 搜索主景区 → 获取 uid + 坐标
    //   2. 调 detail API → 取 children（官方子点，最准确）
    //   3. 若 children 不足，再用 around 周边搜索补充
    //      多关键词一次请求：景点$景区$寺庙$殿$塔$门$亭$阁
    // ---------------------------------------------------------------

    public function collectSubSpots(string $name, string $region, int $radius = 3000): array
    {
        // Step 1: 找主景区
        $mainResult = $this->searchByName($name, $region);
        if (($mainResult['status'] ?? -1) !== 0 || empty($mainResult['results'])) {
            return ['error' => "未找到景区「{$name}」：" . ($mainResult['message'] ?? '无结果')];
        }

        $main = $mainResult['results'][0];
        $lat  = $main['location']['lat'];
        $lng  = $main['location']['lng'];

        $collected = []; // uid => spot

        // Step 2: Detail API 取官方 children
        $detail = $this->getDetail($main['uid']);
        if (($detail['status'] ?? -1) === 0 && !empty($detail['results'])) {
            $children = $detail['results'][0]['children'] ?? [];
            foreach ($children as $child) {
                if (!empty($child['uid'])) {
                    $collected[$child['uid']] = $child;
                }
            }
        }

        // Step 3: 周边搜索补充（多关键词并集，$ 分隔）
        // 只按行业分类过滤（旅游景点），无关景区由后续 AI 步骤剔除
        $aroundKeywords = '景点$景区$寺庙$殿$塔$门$亭$阁$楼$廊$园$院$堂$庙$观';

        for ($page = 0; $page <= 7; $page++) {
            $data = $this->searchAround($lat, $lng, $radius, $aroundKeywords, $page);
            if (($data['status'] ?? -1) !== 0 || empty($data['results'])) break;

            foreach ($data['results'] as $spot) {
                $uid = $spot['uid'] ?? null;
                if ($uid && $uid !== $main['uid'] && !isset($collected[$uid])
                    && $this->isTouristSpot($spot)) {
                    $collected[$uid] = $spot;
                }
            }

            if (count($data['results']) < 20) break;
            usleep(300000); // 300ms 限频
        }

        return [
            'main'     => $main,
            'subSpots' => array_values($collected),
        ];
    }

    // ---------------------------------------------------------------
    // 采集子景点的孙景点
    //
    // 策略：
    //   1. 先调 detail → children（官方子点）
    //   2. 再用小半径 around 补充
    // ---------------------------------------------------------------

    public function collectGrandSpots(string $uid): array
    {
        $collected = [];

        // 只用 detail API 的官方 children，不做 around 周边搜索
        // around 搜索仅凭距离近就建立父子关系，会把同级景点错误挂到子节点下
        $detail = $this->getDetail($uid);
        if (($detail['status'] ?? -1) === 0 && !empty($detail['results'])) {
            foreach ($detail['results'][0]['children'] ?? [] as $child) {
                if (!empty($child['uid'])) {
                    $collected[$child['uid']] = $child;
                }
            }
        }

        return array_values($collected);
    }

    // ---------------------------------------------------------------
    // 采集景区周边配套设施
    // type => query 关键词，radius 默认 2000m
    // ---------------------------------------------------------------

    public function collectFacilities(float $lat, float $lng, string $query, int $radius = 2000): array
    {
        $collected = [];
        for ($page = 0; $page <= 2; $page++) {
            $data = $this->searchAround($lat, $lng, $radius, $query, $page);
            if (($data['status'] ?? -1) !== 0 || empty($data['results'])) break;
            foreach ($data['results'] as $item) {
                $uid = $item['uid'] ?? null;
                if ($uid && !isset($collected[$uid])) {
                    $collected[$uid] = $item;
                }
            }
            if (count($data['results']) < 20) break;
            usleep(300000);
        }
        return array_values($collected);
    }

    public function formatFacility(array $s): array
    {
        $info = $s['detail_info'] ?? [];
        return [
            'baidu_uid'      => $s['uid'] ?? null,
            'name'           => $s['name'] ?? '',
            'address'        => $s['address'] ?? null,
            'lat'            => $s['location']['lat'] ?? null,
            'lng'            => $s['location']['lng'] ?? null,
            'distance'       => $info['distance'] ?? ($s['distance'] ?? null),
            'telephone'      => $s['telephone'] ?? null,
            'overall_rating' => $info['overall_rating'] ?? null,
            'shop_hours'     => $info['shop_hours'] ?? null,
            'price'          => $info['price'] ?? null,
            'tag'            => $info['classified_poi_tag'] ?? ($info['tag'] ?? null),
        ];
    }

    // ---------------------------------------------------------------
    // 从景区名称提取核心关键词（去掉通用后缀）
    // 用于过滤 around 搜索结果，排除与景区无关的 POI
    // ---------------------------------------------------------------

    private function extractNameKeyword(string $name): string
    {
        $suffixes = [
            '旅游风景区', '旅游景区', '旅游区', '风景名胜区', '风景区',
            '国家公园', '遗址公园', '森林公园', '地质公园',
            '游览区', '观光区', '景区', '公园',
        ];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                $name = mb_substr($name, 0, mb_strlen($name) - mb_strlen($suffix));
                break;
            }
        }
        // 去掉省市前缀（如"北京市""滨州市"）
        $name = preg_replace('/^[\x{4e00}-\x{9fa5}]{2,3}[市省]\s*/u', '', $name);
        return $name;
    }

    // 判断 POI 是否属于当前景区（地址或名称包含景区核心词）
    private function belongsToSpot(array $spot, string $keyword): bool
    {
        if (mb_strlen($keyword) < 2) return true; // 关键词太短，不过滤
        $address = $spot['address'] ?? '';
        $name    = $spot['name'] ?? '';
        return str_contains($address, $keyword) || str_contains($name, $keyword);
    }

    // ---------------------------------------------------------------
    // 判断 POI 是否属于旅游景点（一级行业分类）
    // ---------------------------------------------------------------

    private function isTouristSpot(array $spot): bool
    {
        $tag = $spot['detail_info']['classified_poi_tag']
            ?? $spot['classified_poi_tag']
            ?? '';
        return str_starts_with($tag, '旅游景点');
    }

    // ---------------------------------------------------------------
    // 格式化 POI 数据（兼容 v3 结构）
    // ---------------------------------------------------------------

    public function formatSpot(array $s): array
    {
        $info = $s['detail_info'] ?? [];
        return [
            'baidu_uid'      => $s['uid'] ?? null,
            'name'           => $s['name'] ?? ($s['show_name'] ?? ''),
            'address'        => $s['address'] ?? null,
            'lat'            => $s['location']['lat'] ?? null,
            'lng'            => $s['location']['lng'] ?? null,
            'tag'            => $info['classified_poi_tag'] ?? ($info['tag'] ?? ($s['classified_poi_tag'] ?? null)),
            'telephone'      => $s['telephone'] ?? null,
            'overall_rating' => $info['overall_rating'] ?? null,
        ];
    }
}
