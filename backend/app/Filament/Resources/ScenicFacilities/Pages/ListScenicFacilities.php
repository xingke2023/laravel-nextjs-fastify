<?php

namespace App\Filament\Resources\ScenicFacilities\Pages;

use App\Filament\Resources\ScenicFacilities\ScenicFacilityResource;
use Filament\Resources\Pages\ListRecords;

class ListScenicFacilities extends ListRecords
{
    protected static string $resource = ScenicFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
