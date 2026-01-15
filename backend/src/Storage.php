<?php

class Storage
{
    private string $path;
    private array $data;

    public function __construct(string $path)
    {
        $this->path = $path;
        if (!file_exists($path)) {
            $this->data = $this->seed();
            $this->persist();
        } else {
            $raw = file_get_contents($path);
            $this->data = $raw ? json_decode($raw, true) : $this->seed();
        }
    }

    public function data(): array
    {
        return $this->data;
    }

    public function update(callable $mutator): void
    {
        $mutator($this->data);
        $this->persist();
    }

    public function nextId(string $key): int
    {
        if (!isset($this->data['_seq'][$key])) {
            $this->data['_seq'][$key] = 1;
        }
        $this->data['_seq'][$key] += 1;
        return $this->data['_seq'][$key];
    }

    private function persist(): void
    {
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }
        file_put_contents($this->path, json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function seed(): array
    {
        return [
            '_seq' => [
                'user' => 1000,
                'lead' => 2000,
                'attempt' => 3000,
                'report' => 4000,
                'user_task' => 5000,
                'redeem_order' => 6000,
                'user_benefit' => 7000,
                'buddy_match' => 8000,
                'team' => 9000,
                'buddy_report' => 10000,
            ],
            'tokens' => [],
            'user_profile' => [],
            'user_wallet' => [],
            'points_ledger' => [],
            'class_type' => [
                [
                    'id' => 1,
                    'name' => '衡辰尖子班',
                    'description' => '聚焦竞赛与拔尖培养，适合成绩优秀学生。',
                    'plan_quota' => 120,
                    'prereg_count' => 36,
                    'status' => 'open',
                    'show_quota' => true,
                    'threshold_warn' => 90,
                ],
                [
                    'id' => 2,
                    'name' => '衡辰强基班',
                    'description' => '强基计划导向，注重学科基础与综合能力提升。',
                    'plan_quota' => 200,
                    'prereg_count' => 120,
                    'status' => 'paused',
                    'show_quota' => true,
                    'threshold_warn' => 160,
                ],
                [
                    'id' => 3,
                    'name' => '衡辰特色班',
                    'description' => '关注个性发展与兴趣方向，提供成长路径规划。',
                    'plan_quota' => null,
                    'prereg_count' => 58,
                    'status' => 'open',
                    'show_quota' => false,
                    'threshold_warn' => null,
                ],
            ],
            'topic' => [
                [
                    'id' => 1,
                    'name' => '学习能力',
                    'description' => '学习习惯与学习方法评估',
                    'sort' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'name' => '成长潜力',
                    'description' => '综合素养与兴趣潜力评估',
                    'sort' => 2,
                    'status' => 'online',
                ],
            ],
            'test' => [
                [
                    'id' => 1,
                    'topic_id' => 1,
                    'title' => '学习习惯测评',
                    'description' => '了解你的学习节奏与习惯。',
                    'mode' => 'fixed',
                    'question_count' => 3,
                    'time_estimate_sec' => 300,
                    'reward_points' => 20,
                    'reward_xp' => 10,
                    'rule_json' => null,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'topic_id' => 2,
                    'title' => '成长潜力测评',
                    'description' => '探索你的兴趣与潜力方向。',
                    'mode' => 'draw',
                    'question_count' => 3,
                    'time_estimate_sec' => 240,
                    'reward_points' => 15,
                    'reward_xp' => 12,
                    'rule_json' => null,
                    'status' => 'online',
                ],
            ],
            'question' => [
                [
                    'id' => 1,
                    'test_id' => 1,
                    'topic_id' => null,
                    'q_type' => 'single',
                    'stem' => '你会在每天固定的时间复习当天课程吗？',
                    'dimension_key' => 'habit',
                    'weight' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'test_id' => 1,
                    'topic_id' => null,
                    'q_type' => 'single',
                    'stem' => '你通常会提前规划本周学习任务吗？',
                    'dimension_key' => 'plan',
                    'weight' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 3,
                    'test_id' => 1,
                    'topic_id' => null,
                    'q_type' => 'scale',
                    'stem' => '遇到难题时，你的坚持程度如何？',
                    'dimension_key' => 'persevere',
                    'weight' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 4,
                    'test_id' => null,
                    'topic_id' => 2,
                    'q_type' => 'single',
                    'stem' => '你更喜欢哪类校园活动？',
                    'dimension_key' => 'interest',
                    'weight' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 5,
                    'test_id' => null,
                    'topic_id' => 2,
                    'q_type' => 'single',
                    'stem' => '你觉得自己擅长的方向是？',
                    'dimension_key' => 'strength',
                    'weight' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 6,
                    'test_id' => null,
                    'topic_id' => 2,
                    'q_type' => 'scale',
                    'stem' => '面对新挑战时，你的适应速度如何？',
                    'dimension_key' => 'adapt',
                    'weight' => 1,
                    'status' => 'online',
                ],
            ],
            'option' => [
                ['id' => 1, 'question_id' => 1, 'content' => '每天都会', 'score' => 5, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 2, 'question_id' => 1, 'content' => '偶尔会', 'score' => 3, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 3, 'question_id' => 1, 'content' => '很少', 'score' => 1, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 4, 'question_id' => 2, 'content' => '每周都会', 'score' => 5, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 5, 'question_id' => 2, 'content' => '偶尔', 'score' => 3, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 6, 'question_id' => 2, 'content' => '从不', 'score' => 1, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 7, 'question_id' => 3, 'content' => '非常坚持', 'score' => 5, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 8, 'question_id' => 3, 'content' => '一般', 'score' => 3, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 9, 'question_id' => 3, 'content' => '容易放弃', 'score' => 1, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 10, 'question_id' => 4, 'content' => '科技创新', 'score' => 5, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 11, 'question_id' => 4, 'content' => '文艺社团', 'score' => 4, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 12, 'question_id' => 4, 'content' => '体育竞技', 'score' => 3, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 13, 'question_id' => 5, 'content' => '理科思维', 'score' => 5, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 14, 'question_id' => 5, 'content' => '表达沟通', 'score' => 4, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 15, 'question_id' => 5, 'content' => '组织协调', 'score' => 3, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 16, 'question_id' => 6, 'content' => '适应很快', 'score' => 5, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 17, 'question_id' => 6, 'content' => '需要时间', 'score' => 3, 'dimension_key' => null, 'status' => 'online'],
                ['id' => 18, 'question_id' => 6, 'content' => '比较困难', 'score' => 1, 'dimension_key' => null, 'status' => 'online'],
            ],
            'attempt' => [],
            'attempt_item' => [],
            'report' => [],
            'task_def' => [
                [
                    'id' => 1,
                    'task_type' => 'daily',
                    'code' => 'daily_checkin',
                    'title' => '每日打卡',
                    'description' => '完成今日签到，获取成长值。',
                    'rule_json' => ['type' => 'checkin'],
                    'reward_points' => 5,
                    'reward_xp' => 5,
                    'cooldown_sec' => 0,
                    'max_claim_per_day' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'task_type' => 'daily',
                    'code' => 'daily_test',
                    'title' => '完成一次测评',
                    'description' => '任意测评完成即可领取奖励。',
                    'rule_json' => ['type' => 'test'],
                    'reward_points' => 10,
                    'reward_xp' => 8,
                    'cooldown_sec' => 0,
                    'max_claim_per_day' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 3,
                    'task_type' => 'daily',
                    'code' => 'daily_buddy',
                    'title' => '互动搭子',
                    'description' => '完成一次搭子互动。',
                    'rule_json' => ['type' => 'buddy'],
                    'reward_points' => 8,
                    'reward_xp' => 6,
                    'cooldown_sec' => 0,
                    'max_claim_per_day' => 1,
                    'status' => 'online',
                ],
            ],
            'user_task' => [],
            'benefit_def' => [
                [
                    'id' => 1,
                    'benefit_type' => 'coupon',
                    'name' => '校园开放日优先入场券',
                    'description' => '凭券可优先入场参与校园开放日。',
                    'cost_points' => 30,
                    'validity_days' => 30,
                    'stock' => 100,
                    'per_user_limit' => 1,
                    'status' => 'online',
                    'meta_json' => null,
                ],
                [
                    'id' => 2,
                    'benefit_type' => 'service',
                    'name' => '名师一对一咨询',
                    'description' => '预约名师进行学业规划指导。',
                    'cost_points' => 50,
                    'validity_days' => 30,
                    'stock' => 50,
                    'per_user_limit' => 1,
                    'status' => 'online',
                    'meta_json' => null,
                ],
            ],
            'redeem_order' => [],
            'user_benefit' => [],
            'tag_def' => [
                ['id' => 1, 'category' => 'interest', 'name' => '科技创新', 'status' => 'online'],
                ['id' => 2, 'category' => 'interest', 'name' => '文学创作', 'status' => 'online'],
                ['id' => 3, 'category' => 'study', 'name' => '数学竞赛', 'status' => 'online'],
                ['id' => 4, 'category' => 'campus', 'name' => '社团管理', 'status' => 'online'],
                ['id' => 5, 'category' => 'habit', 'name' => '晨读', 'status' => 'online'],
            ],
            'user_tag' => [],
            'buddy_action' => [],
            'buddy_match' => [],
            'buddy_report' => [],
            'team' => [],
            'team_task_def' => [
                [
                    'id' => 1,
                    'title' => '一起完成校园打卡',
                    'description' => '互相分享今日的校园故事。',
                    'rule_json' => ['type' => 'share'],
                    'reward_points' => 5,
                    'reward_xp' => 5,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'title' => '互选主题交流',
                    'description' => '使用预设招呼语完成一次互动。',
                    'rule_json' => ['type' => 'greeting'],
                    'reward_points' => 6,
                    'reward_xp' => 4,
                    'status' => 'online',
                ],
            ],
            'team_task' => [],
            'lead' => [],
            'audit_log' => [],
            'checkin_log' => [],
        ];
    }
}
