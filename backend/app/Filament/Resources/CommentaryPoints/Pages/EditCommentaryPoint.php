<?php

namespace App\Filament\Resources\CommentaryPoints\Pages;

use App\Filament\Resources\CommentaryPoints\CommentaryPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommentaryPoint extends EditRecord
{
    protected static string $resource = CommentaryPointResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
