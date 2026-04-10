<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.deepseek.com/v1';
    private string $model   = 'deepseek-chat';

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    // ---------------------------------------------------------------
    // 核心：批量分析景区节点，返回过滤 + 层级推断结果
    //
    // 返回格式：
    // [
    //   'exclude'   => ['uid1', 'uid2'],                         // 不属于该景区，应删除
    //   'hierarchy' => [['child' => 'uid', 'parent' => 'uid']]  // 父子关系
    // ]
    // ---------------------------------------------------------------

    public function resolveNodes(string $spotName, array $nodes): array
    {
        if (empty($nodes)) return ['exclude' => [], 'hierarchy' => []];

        // 构造节点列表（只发 uid + name + address，减少 token）
        $list = array_map(fn ($n) => [
            'uid'     => $n['baidu_uid'],
            'name'    => $n['name'],
            'address' => $n['address'] ?? '',
        ], $nodes);

        $listJson = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt = <<<PROMPT
你是景区数据整理专家。以下景点列表是在「{$spotName}」周边采集的，因搜索半径较大，混入了周边其他独立景区/公园/场馆的数据。

**任务1 - 过滤（必须严格执行）**：
判断每个景点是否真正位于「{$spotName}」的管辖范围内。凡是拥有独立名称、独立门票、独立管理机构的景区/公园/博物馆/纪念馆，即使紧邻「{$spotName}」，也应列入 exclude。
例如：采集「天坛公园景区」时，毛主席纪念堂、天安门广场、龙潭公园、陶然亭公园都是独立景区，必须 exclude。
宁可多删，不可少删。

**任务2 - 层级推断**：
对保留的景点，若某景点是另一景点的内部区域（如某殿是某寺的子景观），建立父子关系列入 hierarchy。

景点列表：
```json
{$listJson}
```

严格按以下 JSON 格式返回，不要有任何多余文字：
{
  "exclude": ["uid1", "uid2"],
  "hierarchy": [
    {"child": "uid_child", "parent": "uid_parent"}
  ]
}
PROMPT;

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'       => $this->model,
                    'temperature' => 0,
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            $content = $response->json('choices.0.message.content', '{}');
            $result  = json_decode($content, true);

            return [
                'exclude'   => $result['exclude']   ?? [],
                'hierarchy' => $result['hierarchy'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error("DeepSeek resolveNodes error: {$e->getMessage()}");
            return ['exclude' => [], 'hierarchy' => []];
        }
    }

    // ---------------------------------------------------------------
    // 生成讲解词（传入百度 detail 数据，返回结构化讲解骨架）
    // ---------------------------------------------------------------

    public function generateCommentary(string $nodeName, array $detail): string
    {
        $info = $detail['detail_info'] ?? [];
        $ctx  = implode("\n", array_filter([
            isset($info['description'])   ? "简介：{$info['description']}"     : null,
            isset($info['best_time'])     ? "最佳游览时间：{$info['best_time']}" : null,
            isset($info['sug_time'])      ? "建议游玩时长：{$info['sug_time']}"  : null,
            isset($info['overall_rating'])? "评分：{$info['overall_rating']}"   : null,
        ]));

        $prompt = "你是专业景区讲解员。请根据以下资料为「{$nodeName}」生成一段适合语音播报的讲解词（400-600字），"
            . "分为：开场介绍、历史文化背景、主要看点、游览小贴士、结语。语气亲切自然，适合游客。\n\n资料：\n{$ctx}";

        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'       => $this->model,
                    'temperature' => 0.7,
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            return $response->json('choices.0.message.content', '');
        } catch (\Throwable $e) {
            Log::error("DeepSeek generateCommentary error: {$e->getMessage()}");
            return '';
        }
    }
}
