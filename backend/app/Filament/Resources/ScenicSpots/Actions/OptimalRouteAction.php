<?php

namespace App\Filament\Resources\ScenicSpots\Actions;

use App\Models\ScenicNode;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class OptimalRouteAction
{
    public static function make(): Action
    {
        return Action::make('optimalRoute')
            ->label('规划最优路线')
            ->icon('heroicon-o-map')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('规划最优游览路线')
            ->modalDescription('将根据子景点坐标，使用最近邻算法计算最优游览顺序，并更新各景点的 sort_order。确认继续？')
            ->action(function ($record) {
                $spots = $record->subSpots()
                    ->whereNotNull('lat')
                    ->whereNotNull('lng')
                    ->get(['id', 'name', 'lat', 'lng']);

                if ($spots->count() < 2) {
                    Notification::make()
                        ->title('景点数量不足')
                        ->body('需要至少 2 个有坐标的子景点才能规划路线')
                        ->warning()
                        ->send();
                    return;
                }

                $ordered = self::nearestNeighbor($spots->toArray());

                foreach ($ordered as $sortOrder => $spot) {
                    ScenicNode::where('id', $spot['id'])->update(['sort_order' => $sortOrder + 1]);
                }

                Notification::make()
                    ->title('路线规划完成')
                    ->body("已按最优游览顺序更新 {$spots->count()} 个景点的排序")
                    ->success()
                    ->send();
            });
    }

    /**
     * 最近邻 TSP 算法
     * 从第一个景点出发，每次选择距离最近的未访问景点
     */
    private static function nearestNeighbor(array $spots): array
    {
        $remaining = $spots;
        $ordered   = [];

        // 起点：取第一个景点
        $current  = array_shift($remaining);
        $ordered[] = $current;

        while (!empty($remaining)) {
            $nearestIdx = null;
            $minDist    = PHP_FLOAT_MAX;

            foreach ($remaining as $idx => $spot) {
                $dist = self::haversine(
                    (float) $current['lat'], (float) $current['lng'],
                    (float) $spot['lat'],    (float) $spot['lng']
                );
                if ($dist < $minDist) {
                    $minDist    = $dist;
                    $nearestIdx = $idx;
                }
            }

            $current   = $remaining[$nearestIdx];
            $ordered[] = $current;
            unset($remaining[$nearestIdx]);
        }

        return $ordered;
    }

    /**
     * Haversine 公式计算两点球面距离（米）
     */
    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371000; // 地球半径（米）
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
