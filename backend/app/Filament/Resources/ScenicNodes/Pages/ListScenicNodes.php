<?php

namespace App\Filament\Resources\ScenicNodes\Pages;

use App\Filament\Resources\ScenicNodes\ScenicNodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScenicNodes extends ListRecords
{
    protected static string $resource = ScenicNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
