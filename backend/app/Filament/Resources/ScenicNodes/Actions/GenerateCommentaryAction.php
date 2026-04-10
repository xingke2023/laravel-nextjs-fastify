<?php

namespace App\Filament\Resources\ScenicNodes\Actions;

use App\Services\BaiduMapService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GenerateCommentaryAction
{
    public static function make(): Action
    {
        return Action::make('generateCommentary')
            ->label('生成讲解词骨架')
            ->icon('heroicon-o-sparkles')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('自动生成讲解词骨架')
            ->modalDescription('将根据百度地图详情自动填充讲解词骨架模板，已有内容将被覆盖，确认继续？')
            ->action(function ($record) {
                $skeleton = self::buildSkeleton($record);

                $record->update(['commentary' => $skeleton]);

                Notification::make()
                    ->title('讲解词骨架已生成')
                    ->body('请在编辑框中进一步完善内容')
                    ->success()
                    ->send();
            });
    }

    private static function buildSkeleton($record): string
    {
        $detail = [];

        // 从百度 Detail API 拉取丰富信息
        if ($record->baidu_uid) {
            $service = new BaiduMapService();
            $result  = $service->getDetail($record->baidu_uid);
            if (($result['status'] ?? -1) === 0 && !empty($result['results'])) {
                $detail = $result['results'][0];
            }
        }

        $info     = $detail['detail_info'] ?? [];
        $name     = $record->name;
        $tag      = $record->tag ?? ($info['classified_poi_tag'] ?? '');
        $address  = $record->address ?? ($detail['address'] ?? '');
        $rating   = $record->overall_rating ?? ($info['overall_rating'] ?? '');
        $intro    = $info['description'] ?? '';
        $bestTime = $info['best_time'] ?? '';
        $sugTime  = $info['sug_time'] ?? '';
        $hours    = $info['shop_hours'] ?? '';
        $tel      = $record->telephone ?? ($detail['telephone'] ?? '');
        $price    = $info['price'] ?? '';
        $ranking  = $info['ranking'] ?? '';

        $lines = [];

        // —— 开场白 ——
        $lines[] = "【{$name}】";
        $lines[] = '';

        // —— 基本介绍 ——
        if ($intro) {
            $lines[] = $intro;
        } else {
            $lines[] = "各位游客朋友，欢迎来到{$name}。";
            if ($tag)     $lines[] = "这里是一处{$tag}，";
            if ($address) $lines[] = "位于{$address}。";
        }
        $lines[] = '';

        // —— 评价 / 排名 ——
        if ($rating || $ranking) {
            $lines[] = '▌ 口碑评价';
            if ($rating)  $lines[] = "综合评分：{$rating} 分";
            if ($ranking) $lines[] = "排名：{$ranking}";
            $lines[] = '';
        }

        // —— 最佳游览时间 ——
        if ($bestTime) {
            $lines[] = '▌ 最佳游览时间';
            $lines[] = $bestTime;
            $lines[] = '';
        }

        // —— 建议游玩时长 ——
        if ($sugTime) {
            $lines[] = '▌ 建议游玩时长';
            $lines[] = $sugTime;
            $lines[] = '';
        }

        // —— 历史文化 ——
        $lines[] = '▌ 历史文化背景';
        $lines[] = '（请在此补充景点的历史渊源、文化内涵、传说故事等）';
        $lines[] = '';

        // —— 主要看点 ——
        $lines[] = '▌ 主要景观亮点';
        $lines[] = '（请在此列举景点内的核心景观、建筑特色、自然风光等）';
        $lines[] = '';

        // —— 游览建议 ——
        $lines[] = '▌ 游览小贴士';
        if ($hours) $lines[] = "开放时间：{$hours}";
        if ($price) $lines[] = "门票参考：{$price}";
        if ($tel)   $lines[] = "联系电话：{$tel}";
        $lines[] = '（请在此补充游览路线、注意事项、拍照攻略等建议）';
        $lines[] = '';

        // —— 结语 ——
        $lines[] = "感谢各位游览{$name}，祝您旅途愉快！";

        return implode("\n", $lines);
    }
}
