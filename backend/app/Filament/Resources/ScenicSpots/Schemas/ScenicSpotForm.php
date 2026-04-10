<?php

namespace App\Filament\Resources\ScenicSpots\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScenicSpotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('景区名称')
                    ->required()
                    ->maxLength(255),
                Select::make('level')
                    ->label('等级')
                    ->options([
                        '5A' => '5A',
                        '4A' => '4A',
                        '3A' => '3A',
                        '2A' => '2A',
                        '1A' => '1A',
                    ])
                    ->nullable(),
                TextInput::make('province')
                    ->label('省级')
                    ->maxLength(20),
                TextInput::make('address')
                    ->label('地址')
                    ->maxLength(255),
            ]);
    }
}
