<?php

namespace App\Storage;

class JsonStore
{
    private string $path;
    private array $data;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->data = $this->load();
    }

    public function all(string $table): array
    {
        return $this->data[$table] ?? [];
    }

    public function put(string $table, array $rows): void
    {
        $this->data[$table] = $rows;
        $this->persist();
    }

    public function insert(string $table, array $row): array
    {
        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }
        $id = $this->nextId($table);
        $row['id'] = $id;
        $this->data[$table][] = $row;
        $this->persist();

        return $row;
    }

    public function update(string $table, callable $callback): void
    {
        $rows = $this->data[$table] ?? [];
        foreach ($rows as $index => $row) {
            $rows[$index] = $callback($row);
        }
        $this->data[$table] = $rows;
        $this->persist();
    }

    public function find(string $table, callable $callback): ?array
    {
        foreach ($this->data[$table] ?? [] as $row) {
            if ($callback($row)) {
                return $row;
            }
        }

        return null;
    }

    public function filter(string $table, callable $callback): array
    {
        $result = [];
        foreach ($this->data[$table] ?? [] as $row) {
            if ($callback($row)) {
                $result[] = $row;
            }
        }

        return $result;
    }

    public function delete(string $table, callable $callback): void
    {
        $rows = $this->data[$table] ?? [];
        $rows = array_values(array_filter($rows, fn ($row) => !$callback($row)));
        $this->data[$table] = $rows;
        $this->persist();
    }

    public function setMeta(string $key, mixed $value): void
    {
        $this->data['meta'][$key] = $value;
        $this->persist();
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->data['meta'][$key] ?? $default;
    }

    private function nextId(string $table): int
    {
        if (!isset($this->data['meta']['next_ids'])) {
            $this->data['meta']['next_ids'] = [];
        }
        $nextIds = $this->data['meta']['next_ids'];
        $next = ($nextIds[$table] ?? 0) + 1;
        $nextIds[$table] = $next;
        $this->data['meta']['next_ids'] = $nextIds;

        return $next;
    }

    private function load(): array
    {
        if (!file_exists($this->path)) {
            $data = $this->seed();
            $this->write($data);
            return $data;
        }
        $raw = file_get_contents($this->path);
        $decoded = json_decode($raw ?: '', true);
        if (!is_array($decoded)) {
            $decoded = $this->seed();
            $this->write($decoded);
        }

        return $decoded;
    }

    private function persist(): void
    {
        $this->write($this->data);
    }

    private function write(array $data): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function seed(): array
    {
        $now = date('Y-m-d H:i:s');
        return [
            'meta' => [
                'next_ids' => [
                    'user_profile' => 3,
                    'topic' => 2,
                    'test' => 2,
                    'question' => 6,
                    'option' => 18,
                    'class_type' => 2,
                    'task_def' => 3,
                    'user_task' => 3,
                    'benefit_def' => 2,
                    'tag_def' => 6,
                    'attempt' => 0,
                    'attempt_item' => 0,
                    'report' => 0,
                    'redeem_order' => 0,
                    'user_benefit' => 0,
                    'buddy_action' => 0,
                    'buddy_match' => 0,
                    'buddy_report' => 0,
                    'lead' => 0,
                    'team' => 0,
                ],
            ],
            'sessions' => [],
            'user_profile' => [
                [
                    'id' => 1,
                    'openid' => 'seed_user_1',
                    'nickname' => '星耀学员',
                    'avatar_url' => null,
                    'grade' => '初一',
                    'city' => '南昌',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 2,
                    'openid' => 'seed_user_2',
                    'nickname' => '夜幕战士',
                    'avatar_url' => null,
                    'grade' => '初二',
                    'city' => '景德镇',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 3,
                    'openid' => 'seed_user_3',
                    'nickname' => '青岚旅人',
                    'avatar_url' => null,
                    'grade' => '初三',
                    'city' => '上饶',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'user_wallet' => [
                ['user_id' => 1, 'points' => 120, 'xp' => 300, 'level' => 2, 'updated_at' => $now],
                ['user_id' => 2, 'points' => 80, 'xp' => 140, 'level' => 1, 'updated_at' => $now],
                ['user_id' => 3, 'points' => 60, 'xp' => 90, 'level' => 1, 'updated_at' => $now],
            ],
            'topic' => [
                ['id' => 1, 'name' => '学科潜能', 'description' => '学习力与潜能测评', 'sort' => 1, 'status' => 'online'],
                ['id' => 2, 'name' => '性格优势', 'description' => '探索你的成长特质', 'sort' => 2, 'status' => 'online'],
            ],
            'test' => [
                [
                    'id' => 1,
                    'topic_id' => 1,
                    'title' => '学习力挑战',
                    'description' => '10分钟掌握学习力画像',
                    'mode' => 'fixed',
                    'question_count' => 3,
                    'time_estimate_sec' => 600,
                    'reward_points' => 20,
                    'reward_xp' => 30,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'topic_id' => 2,
                    'title' => '性格潜能',
                    'description' => '了解你的优势组合',
                    'mode' => 'fixed',
                    'question_count' => 3,
                    'time_estimate_sec' => 480,
                    'reward_points' => 15,
                    'reward_xp' => 20,
                    'status' => 'online',
                ],
            ],
            'question' => [
                ['id' => 1, 'test_id' => 1, 'topic_id' => 1, 'q_type' => 'single', 'stem' => '面对难题时我会？', 'dimension_key' => 'persist', 'weight' => 1, 'status' => 'online'],
                ['id' => 2, 'test_id' => 1, 'topic_id' => 1, 'q_type' => 'single', 'stem' => '学习计划通常？', 'dimension_key' => 'plan', 'weight' => 1, 'status' => 'online'],
                ['id' => 3, 'test_id' => 1, 'topic_id' => 1, 'q_type' => 'single', 'stem' => '课堂专注度？', 'dimension_key' => 'focus', 'weight' => 1, 'status' => 'online'],
                ['id' => 4, 'test_id' => 2, 'topic_id' => 2, 'q_type' => 'single', 'stem' => '我更喜欢？', 'dimension_key' => 'style', 'weight' => 1, 'status' => 'online'],
                ['id' => 5, 'test_id' => 2, 'topic_id' => 2, 'q_type' => 'single', 'stem' => '面对变化时？', 'dimension_key' => 'adapt', 'weight' => 1, 'status' => 'online'],
                ['id' => 6, 'test_id' => 2, 'topic_id' => 2, 'q_type' => 'single', 'stem' => '团队协作中我？', 'dimension_key' => 'team', 'weight' => 1, 'status' => 'online'],
            ],
            'option' => [
                ['id' => 1, 'question_id' => 1, 'content' => '坚持拆解直到搞懂', 'score' => 5],
                ['id' => 2, 'question_id' => 1, 'content' => '会查资料再继续', 'score' => 3],
                ['id' => 3, 'question_id' => 1, 'content' => '先放一放', 'score' => 1],
                ['id' => 4, 'question_id' => 2, 'content' => '每天安排明确', 'score' => 5],
                ['id' => 5, 'question_id' => 2, 'content' => '有大致方向', 'score' => 3],
                ['id' => 6, 'question_id' => 2, 'content' => '随缘进行', 'score' => 1],
                ['id' => 7, 'question_id' => 3, 'content' => '一直很专注', 'score' => 5],
                ['id' => 8, 'question_id' => 3, 'content' => '偶尔走神', 'score' => 3],
                ['id' => 9, 'question_id' => 3, 'content' => '经常分心', 'score' => 1],
                ['id' => 10, 'question_id' => 4, 'content' => '探索新体验', 'score' => 5],
                ['id' => 11, 'question_id' => 4, 'content' => '稳定踏实', 'score' => 3],
                ['id' => 12, 'question_id' => 4, 'content' => '谨慎观望', 'score' => 1],
                ['id' => 13, 'question_id' => 5, 'content' => '很快适应', 'score' => 5],
                ['id' => 14, 'question_id' => 5, 'content' => '需要时间', 'score' => 3],
                ['id' => 15, 'question_id' => 5, 'content' => '容易焦虑', 'score' => 1],
                ['id' => 16, 'question_id' => 6, 'content' => '愿意担当协调', 'score' => 5],
                ['id' => 17, 'question_id' => 6, 'content' => '做好分内任务', 'score' => 3],
                ['id' => 18, 'question_id' => 6, 'content' => '更喜欢独立', 'score' => 1],
            ],
            'class_type' => [
                ['id' => 1, 'name' => '冲刺班', 'description' => '冲刺重点提升', 'plan_quota' => 30, 'prereg_count' => 18, 'status' => 'open', 'show_quota' => true, 'threshold_warn' => 25],
                ['id' => 2, 'name' => '基础班', 'description' => '夯实基础', 'plan_quota' => 40, 'prereg_count' => 40, 'status' => 'full', 'show_quota' => true, 'threshold_warn' => 35],
            ],
            'task_def' => [
                ['id' => 1, 'task_type' => 'daily', 'code' => 'daily_test', 'title' => '完成一次测评', 'description' => '完成任意测评一次', 'reward_points' => 20, 'reward_xp' => 10, 'status' => 'online'],
                ['id' => 2, 'task_type' => 'daily', 'code' => 'daily_checkin', 'title' => '每日打卡', 'description' => '完成今日打卡', 'reward_points' => 10, 'reward_xp' => 5, 'status' => 'online'],
                ['id' => 3, 'task_type' => 'achievement', 'code' => 'first_report', 'title' => '首份报告', 'description' => '完成首份测评报告', 'reward_points' => 30, 'reward_xp' => 20, 'status' => 'online'],
            ],
            'user_task' => [
                ['id' => 1, 'user_id' => 1, 'task_def_id' => 1, 'progress' => 0, 'target' => 1, 'status' => 'ongoing'],
                ['id' => 2, 'user_id' => 1, 'task_def_id' => 2, 'progress' => 0, 'target' => 1, 'status' => 'ongoing'],
                ['id' => 3, 'user_id' => 1, 'task_def_id' => 3, 'progress' => 0, 'target' => 1, 'status' => 'ongoing'],
            ],
            'benefit_def' => [
                ['id' => 1, 'benefit_type' => 'coupon', 'name' => '模拟卷兑换券', 'description' => '兑换模拟卷一份', 'cost_points' => 50, 'validity_days' => 30, 'stock' => null, 'per_user_limit' => 1, 'status' => 'online'],
                ['id' => 2, 'benefit_type' => 'service', 'name' => '名师答疑券', 'description' => '预约 15 分钟答疑', 'cost_points' => 80, 'validity_days' => 15, 'stock' => 100, 'per_user_limit' => 2, 'status' => 'online'],
            ],
            'tag_def' => [
                ['id' => 1, 'category' => 'interest', 'name' => '电竞策略'],
                ['id' => 2, 'category' => 'interest', 'name' => '二次元绘画'],
                ['id' => 3, 'category' => 'study', 'name' => '数学冲刺'],
                ['id' => 4, 'category' => 'study', 'name' => '英语提升'],
                ['id' => 5, 'category' => 'campus', 'name' => '社团活动'],
                ['id' => 6, 'category' => 'habit', 'name' => '早起打卡'],
            ],
            'user_tag' => [
                ['user_id' => 1, 'tag_id' => 1, 'created_at' => $now],
                ['user_id' => 1, 'tag_id' => 3, 'created_at' => $now],
                ['user_id' => 2, 'tag_id' => 2, 'created_at' => $now],
                ['user_id' => 2, 'tag_id' => 4, 'created_at' => $now],
            ],
            'buddy_action' => [],
            'buddy_match' => [],
            'buddy_report' => [],
            'team' => [],
            'attempt' => [],
            'attempt_item' => [],
            'report' => [],
            'points_ledger' => [],
            'redeem_order' => [],
            'user_benefit' => [],
            'lead' => [],
        ];
    }
}
