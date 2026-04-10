<?php

namespace App\Filament\Resources\ScenicSpots\Pages;

use App\Filament\Resources\ScenicSpots\ScenicSpotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScenicSpot extends EditRecord
{
    protected static string $resource = ScenicSpotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
