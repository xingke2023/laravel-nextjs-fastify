<?php

namespace App\Filament\Resources\ScenicFacilities;

use App\Filament\Resources\ScenicFacilities\Pages\ListScenicFacilities;
use App\Models\ScenicFacility;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ScenicFacilityResource extends Resource
{
    protected static ?string $model = ScenicFacility::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = '周边配套';

    protected static ?string $modelLabel = '配套设施';

    protected static ?string $pluralModelLabel = '周边配套';

    protected static string|\UnitEnum|null $navigationGroup = '景区管理';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('scenicSpot.name')->label('景区')->searchable()->sortable(),
                TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ScenicFacility::$typeLabels[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('name')->label('名称')->searchable()->sortable(),
                TextColumn::make('address')->label('地址')->limit(40)->toggleable(),
                TextColumn::make('distance')->label('距离(m)')->sortable(),
                TextColumn::make('overall_rating')->label('评分')->sortable(),
                TextColumn::make('telephone')->label('电话')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shop_hours')->label('营业时间')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')->label('价格')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tag')->label('标签')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('类型')
                    ->options(ScenicFacility::$typeLabels),
                SelectFilter::make('scenic_spot_id')
                    ->label('景区')
                    ->searchable()
                    ->relationship('scenicSpot', 'name'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScenicFacilities::route('/'),
        ];
    }
}
