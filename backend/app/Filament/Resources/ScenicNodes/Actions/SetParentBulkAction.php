<?php

namespace App\Filament\Resources\ScenicNodes\Actions;

use App\Models\ScenicNode;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class SetParentBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('setParent')
            ->label('挂到父景点')
            ->icon('heroicon-o-arrow-up-on-square')
            ->form(function (Collection $records) {
                // 取所选记录所属景区（可能跨景区，取第一个）
                $spotId = $records->first()?->scenic_spot_id;

                return [
                    Select::make('parent_id')
                        ->label('父景点')
                        ->placeholder('选择父景点')
                        ->searchable()
                        ->options(
                            ScenicNode::where('scenic_spot_id', $spotId)
                                ->whereNull('parent_id')
                                ->whereNotIn('id', $records->pluck('id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->required(),
                ];
            })
            ->action(function (Collection $records, array $data) {
                $parentId = $data['parent_id'];
                $parent   = ScenicNode::find($parentId);

                $records->each(function (ScenicNode $node) use ($parentId) {
                    $node->update(['parent_id' => $parentId]);
                });

                Notification::make()
                    ->title('已更新')
                    ->body("已将 {$records->count()} 个景点挂到「{$parent->name}」下")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
