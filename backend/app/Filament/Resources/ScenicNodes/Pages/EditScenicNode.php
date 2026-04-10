<?php

namespace App\Filament\Resources\ScenicNodes\Pages;

use App\Filament\Resources\ScenicNodes\Actions\GenerateCommentaryAction;
use App\Filament\Resources\ScenicNodes\ScenicNodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScenicNode extends EditRecord
{
    protected static string $resource = ScenicNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateCommentaryAction::make(),
            DeleteAction::make(),
        ];
    }
}
