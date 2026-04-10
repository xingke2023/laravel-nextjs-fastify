<?php

namespace App\Filament\Resources\ScenicNodes\Schemas;

use App\Models\ScenicNode;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScenicNodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('scenic_spot_id')
                        ->relationship('scenicSpot', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('所属景区')
                        ->reactive()
                        ->afterStateUpdated(fn ($set) => $set('parent_id', null)),
                    Select::make('parent_id')
                        ->label('上级景点（孙景点才需选）')
                        ->nullable()
                        ->searchable()
                        ->options(function ($get) {
                            $spotId = $get('scenic_spot_id');
                            if (!$spotId) return [];
                            return ScenicNode::where('scenic_spot_id', $spotId)
                                ->whereNull('parent_id')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        }),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('name')->label('景点名称')->required(),
                    TextInput::make('tag')->label('标签'),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('address')->label('地址'),
                    TextInput::make('telephone')->label('电话')->tel(),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('lat')->label('纬度')->numeric(),
                    TextInput::make('lng')->label('经度')->numeric(),
                    TextInput::make('overall_rating')->label('评分'),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('sort_order')->label('排序')->numeric()->default(0),
                    TextInput::make('baidu_uid')->label('百度UID'),
                ]),
                Textarea::make('commentary')
                    ->label('讲解词')
                    ->rows(10)
                    ->columnSpanFull(),
            ]);
    }
}
