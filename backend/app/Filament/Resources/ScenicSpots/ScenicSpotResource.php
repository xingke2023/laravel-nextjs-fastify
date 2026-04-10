<?php

namespace App\Filament\Resources\ScenicSpots;

use App\Filament\Resources\ScenicSpots\Pages\CreateScenicSpot;
use App\Filament\Resources\ScenicSpots\Pages\EditScenicSpot;
use App\Filament\Resources\ScenicSpots\Pages\ListScenicSpots;
use App\Filament\Resources\ScenicSpots\Schemas\ScenicSpotForm;
use App\Filament\Resources\ScenicSpots\Tables\ScenicSpotsTable;
use App\Models\ScenicSpot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScenicSpotResource extends Resource
{
    protected static ?string $model = ScenicSpot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = '景区列表';

    protected static ?string $modelLabel = '景区';

    protected static ?string $pluralModelLabel = '景区列表';

    protected static string|\UnitEnum|null $navigationGroup = '景区管理';

    public static function form(Schema $schema): Schema
    {
        return ScenicSpotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScenicSpotsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScenicSpots::route('/'),
            'create' => CreateScenicSpot::route('/create'),
            'edit' => EditScenicSpot::route('/{record}/edit'),
        ];
    }
}
