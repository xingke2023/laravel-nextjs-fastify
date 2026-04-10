<?php

namespace App\Filament\Resources\ScenicNodes\Tables;

use App\Models\ScenicSpot;
use App\Filament\Resources\ScenicNodes\Actions\SetParentBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ScenicNodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID')->width(60),
                TextColumn::make('scenicSpot.name')->label('所属景区')->searchable()->sortable(),
                TextColumn::make('parent.name')->label('上级景点')->default('—'),
                TextColumn::make('name')->label('景点名称')->searchable()->sortable(),
                TextColumn::make('tag')->label('标签')->badge()->toggleable(),
                TextColumn::make('overall_rating')->label('评分')->toggleable(),
                IconColumn::make('commentary')
                    ->label('讲解词')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->commentary))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('children_count')
                    ->label('孙景点数')
                    ->counts('children')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('scenic_spot_id')
                    ->label('景区')
                    ->searchable()
                    ->options(fn () => ScenicSpot::orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('level')
                    ->label('层级')
                    ->options([
                        'sub'   => '子景点（一级）',
                        'grand' => '孙景点（二级）',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'sub') {
                            $query->whereNull('parent_id');
                        } elseif ($data['value'] === 'grand') {
                            $query->whereNotNull('parent_id');
                        }
                    }),
                SelectFilter::make('has_commentary')
                    ->label('讲解词状态')
                    ->options([
                        'yes' => '已填写',
                        'no'  => '未填写',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'yes') {
                            $query->whereNotNull('commentary')->where('commentary', '!=', '');
                        } elseif ($data['value'] === 'no') {
                            $query->where(fn ($q) => $q->whereNull('commentary')->orWhere('commentary', ''));
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    SetParentBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Group::make('scenic_spot_id')
                    ->label('景区')
                    ->getTitleFromRecordUsing(fn ($record) => $record->scenicSpot->name ?? '—')
                    ->collapsible(),
            ])
            ->defaultGroup('scenic_spot_id')
            ->defaultSort('scenic_spot_id')
            ->defaultPaginationPageOption(50);
    }
}
