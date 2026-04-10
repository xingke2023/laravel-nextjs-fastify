<?php

namespace App\Filament\Resources\ScenicSpots\Actions;

use App\Models\ScenicNode;
use App\Models\ScenicSpot;
use App\Services\DeepSeekService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ResolveNodeParentsAction
{
    // ---------------------------------------------------------------
    // 主入口：优先用 DeepSeek AI，无 key 则降级到 address 匹配
    // ---------------------------------------------------------------

    public static function resolve(int $scenicSpotId): array
    {
        $deepseek = new DeepSeekService();

        if ($deepseek->isConfigured()) {
            return self::resolveWithAI($scenicSpotId, $deepseek);
        }

        $resolved = self::resolveByAddress($scenicSpotId);
        return ['excluded' => 0, 'resolved' => $resolved];
    }

    // ---------------------------------------------------------------
    // AI 模式：DeepSeek 批量分析节点，返回过滤 + 层级结果
    // ---------------------------------------------------------------

    private static function resolveWithAI(int $scenicSpotId, DeepSeekService $deepseek): array
    {
        $spot  = ScenicSpot::find($scenicSpotId);
        $nodes = ScenicNode::where('scenic_spot_id', $scenicSpotId)
            ->whereNull('parent_id')
            ->get(['id', 'baidu_uid', 'name', 'address'])
            ->toArray();

        if (empty($nodes)) return ['excluded' => 0, 'resolved' => 0];

        $result = $deepseek->resolveNodes($spot->name, $nodes);

        $uidToId = collect($nodes)->pluck('id', 'baidu_uid');

        // 删除不属于该景区的节点（含其子节点）
        $excluded = 0;
        foreach ($result['exclude'] as $uid) {
            $nodeId = $uidToId[$uid] ?? null;
            if (!$nodeId) continue;
            ScenicNode::where('scenic_spot_id', $scenicSpotId)
                ->where(fn ($q) => $q->where('id', $nodeId)->orWhere('parent_id', $nodeId))
                ->delete();
            $excluded++;
        }

        // 建立父子关系
        $resolved = 0;
        foreach ($result['hierarchy'] as $rel) {
            $childId  = $uidToId[$rel['child']]  ?? null;
            $parentId = $uidToId[$rel['parent']] ?? null;
            if (!$childId || !$parentId || $childId === $parentId) continue;
            ScenicNode::where('id', $childId)->update(['parent_id' => $parentId]);
            $resolved++;
        }

        // AI 结果之外再跑一遍 address 匹配，补漏
        $resolved += self::resolveByAddress($scenicSpotId);

        return ['excluded' => $excluded, 'resolved' => $resolved];
    }

    // ---------------------------------------------------------------
    // 降级模式：address 包含父节点 name → 建立父子关系
    // ---------------------------------------------------------------

    public static function resolveByAddress(int $scenicSpotId): int
    {
        $nodes = ScenicNode::where('scenic_spot_id', $scenicSpotId)
            ->whereNull('parent_id')
            ->get();

        $parents = $nodes->filter(fn ($n) => mb_strlen($n->name) >= 3);

        $updated = 0;
        foreach ($nodes as $node) {
            if (empty($node->address)) continue;

            $matched = $parents
                ->filter(fn ($p) => $p->id !== $node->id && str_contains($node->address, $p->name))
                ->sortByDesc(fn ($p) => mb_strlen($p->name))
                ->first();

            if ($matched) {
                $node->update(['parent_id' => $matched->id]);
                $updated++;
            }
        }

        return $updated;
    }

    // ---------------------------------------------------------------
    // Filament Action（景区表格行按钮）
    // ---------------------------------------------------------------

    public static function make(): Action
    {
        $deepseek = new DeepSeekService();
        $mode     = $deepseek->isConfigured() ? 'AI + 地址匹配' : '地址匹配';

        return Action::make('resolveNodeParents')
            ->label('自动归组景点')
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('自动归组景点')
            ->modalDescription("当前模式：{$mode}。将过滤无关景点并推断父子层级，已有父节点的不会被覆盖。")
            ->action(function ($record) {
                $result = self::resolve($record->id);

                Notification::make()
                    ->title('归组完成')
                    ->body("删除无关景点 {$result['excluded']} 个，归属 {$result['resolved']} 个到上级节点")
                    ->success()
                    ->send();
            });
    }
}
