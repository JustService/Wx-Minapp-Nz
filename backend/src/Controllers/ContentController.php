<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Storage\JsonStore;

class ContentController
{
    private JsonStore $store;

    public function __construct(JsonStore $store)
    {
        $this->store = $store;
    }

    public function home(Request $request): Response
    {
        return Response::ok([
            'hero_title' => '星穹成长营',
            'hero_subtitle' => '点亮你的学习技能树',
            'notices' => [
                '本周测评完成可领双倍积分',
                '兴趣搭子组队任务已上线',
            ],
            'quick_entries' => [
                ['title' => '测评中心', 'route' => '/pages/evaluate/index'],
                ['title' => '成长任务', 'route' => '/pages/growth/index'],
                ['title' => '兴趣搭子', 'route' => '/pages/buddy/index'],
            ],
        ]);
    }

    public function school(Request $request): Response
    {
        return Response::ok([
            'name' => '南召衡辰中学',
            'intro' => '以素养提升与能力成长为核心的升学服务平台。',
            'highlights' => [
                '名师领航',
                '分层教学',
                '竞赛/升学一体化辅导',
            ],
            'contact' => [
                'phone' => '0791-0000000',
                'address' => '南召县衡辰大道 88 号',
            ],
        ]);
    }

    public function classes(Request $request): Response
    {
        return Response::ok([
            'items' => $this->store->all('class_type'),
        ]);
    }

    public function classDetail(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $class = $this->store->find('class_type', fn ($row) => $row['id'] === $id);
        if (!$class) {
            return Response::error(404, '班型不存在');
        }
        return Response::ok($class);
    }
}
