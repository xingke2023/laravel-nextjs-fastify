<?php

namespace App\Filament\Resources\ScenicSpots\Tables;

use App\Filament\Resources\ScenicSpots\Actions\CollectFacilitiesAction;
use App\Filament\Resources\ScenicSpots\Actions\CollectSubSpotsAction;
use App\Filament\Resources\ScenicSpots\Actions\OptimalRouteAction;
use App\Filament\Resources\ScenicSpots\Actions\ResolveNodeParentsAction;
use App\Jobs\CollectScenicNodeJob;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ScenicSpotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('name')->label('景区名称')->searchable()->sortable(),
                TextColumn::make('level')->label('等级')->badge()->sortable(),
                TextColumn::make('province')->label('省级')->searchable()->sortable(),
                TextColumn::make('address')->label('地址')->limit(40)->searchable()->toggleable(),
                TextColumn::make('nodes_count')->label('景点数')->counts('nodes')->sortable(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('等级')
                    ->options(['5A' => '5A', '4A' => '4A', '3A' => '3A', '2A' => '2A', '1A' => '1A']),
                SelectFilter::make('province')
                    ->label('省级')
                    ->searchable()
                    ->options(fn () => \App\Models\ScenicSpot::distinct()->orderBy('province')->pluck('province', 'province')->toArray()),
            ])
            ->recordActions([
                CollectSubSpotsAction::make(),
                CollectFacilitiesAction::make(),
                OptimalRouteAction::make(),
                ResolveNodeParentsAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('batchCollect')
                        ->label('批量采集子景点（队列）')
                        ->icon('heroicon-o-queue-list')
                        ->color('info')
                        ->form([
                            TextInput::make('radius')->label('子景点搜索半径（米）')->numeric()->default(1500)->helperText('5A建议3000m，4A建议1500m，其他1000m')->required(),
                            Toggle::make('collect_grand')->label('同时采集孙景点（仅百度官方子点）')->default(true),
                        ])
                        ->action(function ($records, array $data) {
                            $userId = Auth::id();
                            $count  = 0;
                            foreach ($records as $record) {
                                dispatch(new CollectScenicNodeJob(
                                    scenicSpotId: $record->id,
                                    radius:       (int) $data['radius'],
                                    collectGrand: (bool) $data['collect_grand'],
                                    notifyUserId: $userId,
                                ));
                                $count++;
                            }
                            Notification::make()
                                ->title('已加入队列')
                                ->body("共 {$count} 个景区已进入后台采集队列，完成后将通知您")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id');
    }
}
