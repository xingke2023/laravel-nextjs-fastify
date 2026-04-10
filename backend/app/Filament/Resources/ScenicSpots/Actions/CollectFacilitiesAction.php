<?php

namespace App\Filament\Resources\ScenicSpots\Actions;

use App\Models\ScenicFacility;
use App\Services\BaiduMapService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class CollectFacilitiesAction
{
    public static function make(): Action
    {
        return Action::make('collectFacilities')
            ->label('采集周边配套')
            ->icon('heroicon-o-building-storefront')
            ->color('warning')
            ->form([
                TextInput::make('radius')
                    ->label('搜索半径（米）')
                    ->numeric()
                    ->default(2000)
                    ->required(),
                CheckboxList::make('types')
                    ->label('设施类型')
                    ->options(ScenicFacility::$typeLabels)
                    ->default(array_keys(ScenicFacility::$typeLabels))
                    ->columns(3),
            ])
            ->action(function (array $data, $record) {
                // 需要景区坐标，先从已采集的子景点或百度搜索获取
                $service = new BaiduMapService();

                // 尝试从已有子景点取坐标
                $firstNode = $record->nodes()->whereNotNull('lat')->first();
                if ($firstNode) {
                    $lat = (float) $firstNode->lat;
                    $lng = (float) $firstNode->lng;
                } else {
                    // 重新搜索获取坐标
                    $mainResult = $service->searchByName($record->name, $record->province ?? '');
                    if (($mainResult['status'] ?? -1) !== 0 || empty($mainResult['results'])) {
                        Notification::make()->title('获取坐标失败')->body('请先采集子景点以获取景区坐标')->danger()->send();
                        return;
                    }
                    $lat = $mainResult['results'][0]['location']['lat'];
                    $lng = $mainResult['results'][0]['location']['lng'];
                }

                $types  = $data['types'] ?? [];
                $radius = (int) $data['radius'];
                $total  = 0;

                foreach ($types as $type) {
                    $query = ScenicFacility::$typeQueries[$type] ?? $type;
                    $items = $service->collectFacilities($lat, $lng, $query, $radius);

                    foreach ($items as $item) {
                        $fmt = $service->formatFacility($item);
                        if (empty($fmt['baidu_uid'])) continue;

                        $created = ScenicFacility::firstOrCreate(
                            ['scenic_spot_id' => $record->id, 'baidu_uid' => $fmt['baidu_uid']],
                            array_merge($fmt, ['scenic_spot_id' => $record->id, 'type' => $type])
                        );
                        if ($created->wasRecentlyCreated) $total++;
                    }
                }

                Notification::make()
                    ->title('配套采集完成')
                    ->body("共新增 {$total} 条配套设施")
                    ->success()
                    ->send();
            });
    }
}
