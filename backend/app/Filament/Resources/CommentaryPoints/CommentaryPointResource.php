<?php

namespace App\Filament\Resources\CommentaryPoints;

use App\Filament\Resources\CommentaryPoints\Pages\CreateCommentaryPoint;
use App\Filament\Resources\CommentaryPoints\Pages\EditCommentaryPoint;
use App\Filament\Resources\CommentaryPoints\Pages\ListCommentaryPoints;
use App\Models\CommentaryPoint;
use App\Models\ScenicNode;
use App\Models\ScenicSpot;
use App\Services\BaiduMapService;
use BackedEnum;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CommentaryPointResource extends Resource
{
    protected static ?string $model = CommentaryPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMicrophone;

    protected static ?string $navigationLabel = '讲解点';

    protected static ?string $modelLabel = '讲解点';

    protected static ?string $pluralModelLabel = '讲解点列表';

    protected static string|\UnitEnum|null $navigationGroup = '景区管理';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // 关联关系
            Grid::make(2)->schema([
                Select::make('scenic_spot_id')
                    ->label('所属景区')
                    ->relationship('scenicSpot', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($set) => $set('scenic_node_id', null)),
                Select::make('scenic_node_id')
                    ->label('所属景点（可选，空=景区级讲解点）')
                    ->nullable()
                    ->searchable()
                    ->options(function ($get) {
                        $spotId = $get('scenic_spot_id');
                        if (!$spotId) return [];
                        return ScenicNode::where('scenic_spot_id', $spotId)
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($n) => [
                                $n->id => ($n->parent_id ? '  └ ' : '') . $n->name,
                            ])
                            ->toArray();
                    }),
            ]),

            // 基本信息
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->label('讲解标题')
                    ->required()
                    ->maxLength(100),
                Grid::make(3)->schema([
                    TextInput::make('sort_order')->label('排序')->numeric()->default(0),
                    TextInput::make('duration')->label('驻留时长(分钟)')->numeric()->nullable(),
                    TextInput::make('trigger_radius')->label('GPS触发半径(米)')->numeric()->default(30),
                ])->columnSpan(1),
            ]),

            // 位置
            Grid::make(2)->schema([
                TextInput::make('lat')->label('纬度')->numeric()->nullable(),
                TextInput::make('lng')->label('经度')->numeric()->nullable(),
            ]),

            // 媒体资源
            Grid::make(2)->schema([
                TextInput::make('audio_url')->label('音频 URL')->url()->nullable()->columnSpan(1),
                TextInput::make('image_url')->label('配图 URL')->url()->nullable()->columnSpan(1),
            ]),

            // 讲解正文
            Textarea::make('content')
                ->label('讲解正文（导游/APP展示）')
                ->rows(12)
                ->columnSpanFull()
                ->hintAction(
                    FormAction::make('generateSkeleton')
                        ->label('生成讲解词骨架')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->action(function ($get, $set) {
                            $title  = $get('title') ?: '该讲解点';
                            $detail = [];

                            // 优先用景点的 baidu_uid
                            $nodeId = $get('scenic_node_id');
                            $uid    = null;

                            if ($nodeId) {
                                $node = ScenicNode::find($nodeId);
                                $uid  = $node?->baidu_uid;
                            }

                            // 若无景点 uid，用景区名称搜索
                            if (!$uid) {
                                $spotId = $get('scenic_spot_id');
                                $spot   = $spotId ? ScenicSpot::find($spotId) : null;
                                if ($spot) {
                                    $service    = new BaiduMapService();
                                    $searchResult = $service->searchByName($spot->name, $spot->province ?? '');
                                    $uid = $searchResult['results'][0]['uid'] ?? null;
                                }
                            }

                            if ($uid) {
                                $service  = new BaiduMapService();
                                $response = $service->getDetail($uid);
                                if (($response['status'] ?? -1) === 0 && !empty($response['results'])) {
                                    $detail = $response['results'][0];
                                }
                            }

                            $info     = $detail['detail_info'] ?? [];
                            $intro    = $info['description'] ?? '';
                            $bestTime = $info['best_time'] ?? '';
                            $sugTime  = $info['sug_time'] ?? '';
                            $hours    = $info['shop_hours'] ?? '';
                            $price    = $info['price'] ?? '';
                            $rating   = $info['overall_rating'] ?? '';
                            $ranking  = $info['ranking'] ?? '';

                            $lines = [];
                            $lines[] = "【{$title}】";
                            $lines[] = '';

                            // 简介
                            if ($intro) {
                                $lines[] = $intro;
                            } else {
                                $lines[] = "欢迎来到{$title}。";
                            }
                            $lines[] = '';

                            // 评价
                            if ($rating || $ranking) {
                                $lines[] = '▌ 评价';
                                if ($rating)  $lines[] = "综合评分：{$rating}";
                                if ($ranking) $lines[] = "排名：{$ranking}";
                                $lines[] = '';
                            }

                            // 最佳时间
                            if ($bestTime) {
                                $lines[] = '▌ 最佳游览时间';
                                $lines[] = $bestTime;
                                $lines[] = '';
                            }
                            if ($sugTime) {
                                $lines[] = '▌ 建议停留时长';
                                $lines[] = $sugTime;
                                $lines[] = '';
                            }

                            // 核心内容占位
                            $lines[] = '▌ 讲解要点';
                            $lines[] = '（请在此描述该讲解点的核心看点、历史背景、文化意义、艺术特色等）';
                            $lines[] = '';

                            // 互动引导
                            $lines[] = '▌ 互动引导';
                            $lines[] = '（可加入：请游客注意观察……；这里流传着一个故事……；有兴趣的朋友可以……）';
                            $lines[] = '';

                            // 实用信息
                            $hasInfo = $hours || $price;
                            if ($hasInfo) {
                                $lines[] = '▌ 实用信息';
                                if ($hours) $lines[] = "开放时间：{$hours}";
                                if ($price) $lines[] = "参考票价：{$price}";
                                $lines[] = '';
                            }

                            $lines[] = "以上就是{$title}的介绍，请大家自由参观，我们 XX 分钟后在此集合。";

                            $set('content', implode("\n", $lines));

                            Notification::make()
                                ->title('骨架已生成')
                                ->body('请在正文区进一步完善内容')
                                ->success()
                                ->send();
                        })
                ),

            Toggle::make('is_published')
                ->label('已上线')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('scenicSpot.name')->label('景区')->searchable()->sortable(),
                TextColumn::make('scenicNode.name')->label('所属景点')->placeholder('景区级')->sortable(),
                TextColumn::make('sort_order')->label('序号')->sortable(),
                TextColumn::make('title')->label('讲解标题')->searchable()->limit(30),
                IconColumn::make('audio_url')->label('音频')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedMusicalNote)
                    ->falseIcon(Heroicon::OutlinedXMark)
                    ->getStateUsing(fn ($record) => !empty($record->audio_url)),
                TextColumn::make('duration')->label('时长(分)')->sortable(),
                TextColumn::make('trigger_radius')->label('触发(m)')->sortable(),
                IconColumn::make('is_published')->label('上线')->boolean()->sortable(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime('Y-m-d')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('scenic_spot_id')
                    ->label('景区')
                    ->searchable()
                    ->relationship('scenicSpot', 'name'),
                TernaryFilter::make('scenic_node_id')
                    ->label('类型')
                    ->placeholder('全部')
                    ->trueLabel('景点级讲解点')
                    ->falseLabel('景区级讲解点')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('scenic_node_id'),
                        false: fn ($query) => $query->whereNull('scenic_node_id'),
                    ),
                TernaryFilter::make('is_published')
                    ->label('上线状态')
                    ->placeholder('全部')
                    ->trueLabel('已上线')
                    ->falseLabel('未上线'),
            ])
            ->defaultSort('scenic_spot_id')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCommentaryPoints::route('/'),
            'create' => CreateCommentaryPoint::route('/create'),
            'edit'   => EditCommentaryPoint::route('/{record}/edit'),
        ];
    }
}
