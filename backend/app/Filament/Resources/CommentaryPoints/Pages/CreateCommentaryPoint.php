<?php

namespace App\Filament\Resources\CommentaryPoints\Pages;

use App\Filament\Resources\CommentaryPoints\CommentaryPointResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommentaryPoint extends CreateRecord
{
    protected static string $resource = CommentaryPointResource::class;
}
