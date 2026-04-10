<?php

namespace App\Filament\Resources\ScenicNodes;

use App\Filament\Resources\ScenicNodes\Pages\CreateScenicNode;
use App\Filament\Resources\ScenicNodes\Pages\EditScenicNode;
use App\Filament\Resources\ScenicNodes\Pages\ListScenicNodes;
use App\Filament\Resources\ScenicNodes\Schemas\ScenicNodeForm;
use App\Filament\Resources\ScenicNodes\Tables\ScenicNodesTable;
use App\Models\ScenicNode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScenicNodeResource extends Resource
{
    protected static ?string $model = ScenicNode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = '景点';

    protected static ?string $modelLabel = '景点';

    protected static ?string $pluralModelLabel = '景点列表';

    protected static string|\UnitEnum|null $navigationGroup = '景区管理';

    public static function form(Schema $schema): Schema
    {
        return ScenicNodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScenicNodesTable::configure($table);
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
            'index' => ListScenicNodes::route('/'),
            'create' => CreateScenicNode::route('/create'),
            'edit' => EditScenicNode::route('/{record}/edit'),
        ];
    }
}
