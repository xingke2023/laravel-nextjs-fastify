<?php

namespace App\Filament\Resources\ScenicSpots\Actions;

use App\Models\ScenicNode;
use App\Services\BaiduMapService;
use App\Filament\Resources\ScenicSpots\Actions\ResolveNodeParentsAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class CollectSubSpotsAction
{
    public static function make(): Action
    {
        return Action::make('collectSubSpots')
            ->label('采集子景点')
            ->icon('heroicon-o-cloud-arrow-down')
            ->color('info')
            ->fillForm(fn ($record) => [
                'radius' => match ($record->level) {
                    '5A'    => 3000,
                    '4A'    => 1500,
                    default => 1000,
                },
            ])
            ->form([
                TextInput::make('radius')
                    ->label('子景点搜索半径（米）')
                    ->numeric()
                    ->helperText('已按景区等级自动建议：5A=3000m，4A=1500m，其他=1000m')
                    ->required(),
                Toggle::make('collect_grand')
                    ->label('同时采集孙景点（仅百度官方子点）')
                    ->default(true),
            ])
            ->action(function (array $data, $record) {
                $service     = new BaiduMapService();
                $radius       = (int) ($data['radius'] ?? 3000);
                $collectGrand = (bool) ($data['collect_grand'] ?? true);

                // 采集子景点
                $result = $service->collectSubSpots($record->name, $record->province ?? '', $radius);

                if (isset($result['error'])) {
                    Notification::make()->title('采集失败')->body($result['error'])->danger()->send();
                    return;
                }

                $subCount   = 0;
                $grandCount = 0;

                foreach ($result['subSpots'] as $i => $spot) {
                    $formatted = $service->formatSpot($spot);
                    if (empty($formatted['baidu_uid'])) continue;

                    $node = ScenicNode::firstOrCreate(
                        [
                            'scenic_spot_id' => $record->id,
                            'baidu_uid'      => $formatted['baidu_uid'],
                        ],
                        array_merge($formatted, [
                            'scenic_spot_id' => $record->id,
                            'parent_id'      => null,
                            'sort_order'     => $i,
                        ])
                    );

                    if ($node->wasRecentlyCreated) $subCount++;

                    // 采集孙景点（仅用 detail API children，不做 around 搜索）
                    if ($collectGrand && $formatted['baidu_uid']) {
                        $grandSpots = $service->collectGrandSpots($formatted['baidu_uid']);

                        foreach ($grandSpots as $j => $grandSpot) {
                            $gf = $service->formatSpot($grandSpot);
                            if (empty($gf['baidu_uid'])) continue;

                            $grand = ScenicNode::firstOrCreate(
                                [
                                    'scenic_spot_id' => $record->id,
                                    'baidu_uid'      => $gf['baidu_uid'],
                                ],
                                array_merge($gf, [
                                    'scenic_spot_id' => $record->id,
                                    'parent_id'      => $node->id,
                                    'sort_order'     => $j,
                                ])
                            );

                            if ($grand->wasRecentlyCreated) $grandCount++;
                        }

                        usleep(200000); // 200ms 限频
                    }
                }

                // AI + address 自动过滤无关景点、推断父子关系
                $ai = ResolveNodeParentsAction::resolve($record->id);

                Notification::make()
                    ->title('采集完成')
                    ->body("新增 {$subCount} 个景点，删除无关 {$ai['excluded']} 个，归组 {$ai['resolved']} 个")
                    ->success()
                    ->send();
            });
    }
}
