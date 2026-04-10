<?php

namespace App\Filament\Resources\ScenicSpots\Pages;

use App\Filament\Resources\ScenicSpots\ScenicSpotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScenicSpots extends ListRecords
{
    protected static string $resource = ScenicSpotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
