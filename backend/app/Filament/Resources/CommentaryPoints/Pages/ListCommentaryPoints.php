<?php

namespace App\Filament\Resources\CommentaryPoints\Pages;

use App\Filament\Resources\CommentaryPoints\CommentaryPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommentaryPoints extends ListRecords
{
    protected static string $resource = CommentaryPointResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
