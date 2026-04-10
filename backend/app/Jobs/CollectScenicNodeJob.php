<?php

namespace App\Jobs;

use App\Filament\Resources\ScenicSpots\Actions\ResolveNodeParentsAction;
use App\Models\ScenicNode;
use App\Models\ScenicSpot;
use App\Services\BaiduMapService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CollectScenicNodeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(
        public readonly int $scenicSpotId,
        public readonly int $radius       = 1000,
        public readonly bool $collectGrand = true,
        public readonly ?int $notifyUserId = null,
    ) {}

    public function handle(): void
    {
        $spot    = ScenicSpot::findOrFail($this->scenicSpotId);
        $service = new BaiduMapService();

        $result = $service->collectSubSpots($spot->name, $spot->province ?? '', $this->radius);

        if (isset($result['error'])) {
            $this->sendNotification("【{$spot->name}】采集失败", $result['error'], 'danger');
            return;
        }

        $subCount   = 0;
        $grandCount = 0;

        foreach ($result['subSpots'] as $i => $spot_data) {
            $fmt = $service->formatSpot($spot_data);
            if (empty($fmt['baidu_uid'])) continue;

            $node = ScenicNode::firstOrCreate(
                ['scenic_spot_id' => $spot->id, 'baidu_uid' => $fmt['baidu_uid']],
                array_merge($fmt, ['scenic_spot_id' => $spot->id, 'parent_id' => null, 'sort_order' => $i])
            );
            if ($node->wasRecentlyCreated) $subCount++;

            if ($this->collectGrand && $fmt['baidu_uid']) {
                $grandSpots = $service->collectGrandSpots($fmt['baidu_uid']);
                foreach ($grandSpots as $j => $gs) {
                    $gf = $service->formatSpot($gs);
                    if (empty($gf['baidu_uid'])) continue;
                    $grand = ScenicNode::firstOrCreate(
                        ['scenic_spot_id' => $spot->id, 'baidu_uid' => $gf['baidu_uid']],
                        array_merge($gf, ['scenic_spot_id' => $spot->id, 'parent_id' => $node->id, 'sort_order' => $j])
                    );
                    if ($grand->wasRecentlyCreated) $grandCount++;
                }
                usleep(200000);
            }
        }

        // AI + address 自动过滤无关景点、推断父子关系
        $ai = ResolveNodeParentsAction::resolve($spot->id);

        $this->sendNotification(
            "【{$spot->name}】采集完成",
            "新增 {$subCount} 个景点，删除无关 {$ai['excluded']} 个，归组 {$ai['resolved']} 个",
            'success'
        );
    }

    private function sendNotification(string $title, string $body, string $type): void
    {
        if (!$this->notifyUserId) return;
        $notification = Notification::make()->title($title)->body($body);
        match ($type) {
            'success' => $notification->success(),
            'danger'  => $notification->danger(),
            default   => $notification->info(),
        };
        $notification->sendToDatabase(\App\Models\User::find($this->notifyUserId));
    }
}
