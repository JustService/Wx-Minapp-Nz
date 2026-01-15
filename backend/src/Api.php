<?php

class Api
{
    private Storage $storage;
    private array $request;
    private ?array $user;

    public function __construct(Storage $storage, array $request, ?array $user)
    {
        $this->storage = $storage;
        $this->request = $request;
        $this->user = $user;
    }

    public function login(): array
    {
        $payload = $this->request['body'];
        $code = $payload['code'] ?? null;
        if (!$code) {
            return Response::error(400, 'code required');
        }
        $openid = 'openid_' . $code;
        $storage = $this->storage;
        $token = bin2hex(random_bytes(16));
        $storage->update(function (&$data) use ($openid, $token) {
            $user = $this->findBy($data['user_profile'], 'openid', $openid);
            if (!$user) {
                $userId = $this->nextId($data, 'user');
                $user = [
                    'id' => $userId,
                    'openid' => $openid,
                    'nickname' => '衡辰新同学',
                    'avatar_url' => null,
                    'grade' => null,
                    'city' => null,
                    'privacy_settings' => new stdClass(),
                    'status' => 'active',
                ];
                $data['user_profile'][] = $user;
                $data['user_wallet'][] = [
                    'user_id' => $userId,
                    'points' => 0,
                    'xp' => 0,
                    'level' => 1,
                ];
            }
            $data['tokens'][$token] = $user['id'];
        });
        $user = $this->findBy($storage->data()['user_profile'], 'openid', $openid);
        $wallet = $this->findBy($storage->data()['user_wallet'], 'user_id', $user['id']);
        $this->audit('auth.login', ['openid' => $openid]);
        return Response::success([
            'access_token' => $token,
            'profile' => $user,
            'wallet' => $wallet,
        ]);
    }

    public function contentHome(): array
    {
        return Response::success([
            'school_name' => '南召衡辰中学',
            'hero' => [
                'title' => '衡辰主城 · 招生服务中心',
                'subtitle' => '测评、成长、权益与兴趣搭子一站式体验',
            ],
            'modules' => [
                ['title' => '招生政策', 'desc' => '了解最新招生时间与政策解读'],
                ['title' => '校园风采', 'desc' => '紫色主城风格展示校园文化'],
                ['title' => '预约咨询', 'desc' => '一键预约导师沟通'],
            ],
        ]);
    }

    public function contentSchool(): array
    {
        return Response::success([
            'name' => '南召衡辰中学',
            'slogan' => '厚德衡辰 · 恒心致远',
            'intro' => '南召衡辰中学以“修身、笃学、创新、担当”为育人理念，构建多元成长路径。',
            'highlights' => [
                '衡辰尖子班、强基班、特色班多层次培养',
                '名师团队与个性化指导体系',
                '兴趣社团与成长任务双线驱动',
            ],
            'contact' => [
                'phone' => '0377-12345678',
                'address' => '河南省南召县衡辰路88号',
            ],
        ]);
    }

    public function classList(): array
    {
        return Response::success(['items' => $this->storage->data()['class_type']]);
    }

    public function classDetail(int $id): array
    {
        $class = $this->findBy($this->storage->data()['class_type'], 'id', $id);
        if (!$class) {
            return Response::error(404, 'class not found');
        }
        return Response::success($class);
    }

    public function topicList(): array
    {
        return Response::success(['items' => $this->storage->data()['topic']]);
    }

    public function testList(?int $topicId): array
    {
        $tests = $this->storage->data()['test'];
        if ($topicId) {
            $tests = array_values(array_filter($tests, fn ($item) => $item['topic_id'] === $topicId));
        }
        return Response::success(['items' => $tests]);
    }

    public function testDetail(int $id): array
    {
        $test = $this->findBy($this->storage->data()['test'], 'id', $id);
        if (!$test) {
            return Response::error(404, 'test not found');
        }
        return Response::success($test);
    }

    public function startTest(int $id): array
    {
        $test = $this->findBy($this->storage->data()['test'], 'id', $id);
        if (!$test) {
            return Response::error(404, 'test not found');
        }
        $seed = bin2hex(random_bytes(8));
        $questions = $this->pickQuestions($test, $seed);
        $attemptId = null;
        $this->storage->update(function (&$data) use ($test, $seed, $questions, &$attemptId) {
            $attemptId = $this->nextId($data, 'attempt');
            $data['attempt'][] = [
                'id' => $attemptId,
                'user_id' => $this->user['id'],
                'test_id' => $test['id'],
                'attempt_seed' => $seed,
                'status' => 'draft',
                'started_at' => $this->now(),
                'submitted_at' => null,
            ];
            $order = 1;
            foreach ($questions as $question) {
                $data['attempt_item'][] = [
                    'id' => $this->nextId($data, 'attempt_item'),
                    'attempt_id' => $attemptId,
                    'question_id' => $question['id'],
                    'question_order' => $order,
                    'option_order_json' => $question['option_order'],
                    'answer_json' => null,
                    'score' => 0,
                ];
                $order += 1;
            }
        });
        $this->audit('evaluate.start', ['test_id' => $id, 'attempt_id' => $attemptId]);
        $responseQuestions = array_map(function ($question) {
            return [
                'question_id' => $question['id'],
                'q_type' => $question['q_type'],
                'stem' => $question['stem'],
                'dimension_key' => $question['dimension_key'],
                'options' => array_map(fn ($opt) => ['option_id' => $opt['id'], 'content' => $opt['content']], $question['options']),
            ];
        }, $questions);
        return Response::success([
            'attempt_id' => $attemptId,
            'attempt_seed' => $seed,
            'questions' => $responseQuestions,
        ]);
    }

    public function saveAttempt(int $id): array
    {
        $answers = $this->request['body']['answers'] ?? null;
        if (!is_array($answers)) {
            return Response::error(400, 'answers required');
        }
        $this->storage->update(function (&$data) use ($id, $answers) {
            foreach ($answers as $answer) {
                foreach ($data['attempt_item'] as &$item) {
                    if ($item['attempt_id'] === $id && $item['question_id'] === $answer['question_id']) {
                        $item['answer_json'] = $answer['answer'];
                    }
                }
            }
        });
        $this->audit('evaluate.save', ['attempt_id' => $id]);
        return Response::success(new stdClass());
    }

    public function submitAttempt(int $id): array
    {
        $payload = $this->request['body'];
        $idem = $payload['idempotency_key'] ?? null;
        $answers = $payload['answers'] ?? null;
        if (!$idem || !is_array($answers)) {
            return Response::error(400, 'idempotency_key and answers required');
        }
        $data = $this->storage->data();
        $attempt = $this->findBy($data['attempt'], 'id', $id);
        if (!$attempt) {
            return Response::error(404, 'attempt not found');
        }
        if ($attempt['status'] === 'submitted') {
            $report = $this->findBy($data['report'], 'attempt_id', $id);
            return Response::success(['report' => $report, 'reward' => ['points' => 0, 'xp' => 0]]);
        }
        $ledgerKey = $this->findLedger($data['points_ledger'], $this->user['id'], 'test', $idem);
        if ($ledgerKey) {
            $report = $this->findBy($data['report'], 'attempt_id', $id);
            return Response::success(['report' => $report, 'reward' => ['points' => 0, 'xp' => 0]]);
        }
        $scoreResult = $this->scoreAttempt($data, $attempt, $answers);
        $report = null;
        $reward = ['points' => $scoreResult['reward_points'], 'xp' => $scoreResult['reward_xp']];
        $this->storage->update(function (&$data) use ($id, $idem, $attempt, $scoreResult, &$report, $reward) {
            foreach ($data['attempt'] as &$item) {
                if ($item['id'] === $id) {
                    $item['status'] = 'submitted';
                    $item['submitted_at'] = $this->now();
                }
            }
            $reportId = $this->nextId($data, 'report');
            $report = [
                'id' => $reportId,
                'user_id' => $this->user['id'],
                'test_id' => $attempt['test_id'],
                'attempt_id' => $id,
                'summary' => $scoreResult['summary'],
                'result_json' => $scoreResult['result_json'],
                'created_at' => $this->now(),
            ];
            $data['report'][] = $report;
            $data['points_ledger'][] = [
                'id' => $this->nextId($data, 'points_ledger'),
                'user_id' => $this->user['id'],
                'delta_points' => $reward['points'],
                'delta_xp' => $reward['xp'],
                'biz_type' => 'test',
                'biz_id' => $attempt['test_id'],
                'idempotency_key' => $idem,
                'remark' => '测评奖励',
                'created_at' => $this->now(),
            ];
            foreach ($data['user_wallet'] as &$wallet) {
                if ($wallet['user_id'] === $this->user['id']) {
                    $wallet['points'] += $reward['points'];
                    $wallet['xp'] += $reward['xp'];
                    if ($wallet['xp'] >= 100) {
                        $wallet['level'] += intdiv($wallet['xp'], 100);
                        $wallet['xp'] = $wallet['xp'] % 100;
                    }
                }
            }
        });
        $this->audit('evaluate.submit', ['attempt_id' => $id, 'reward' => $reward]);
        return Response::success(['report' => $report, 'reward' => $reward]);
    }

    public function reportList(?int $testId): array
    {
        $reports = array_values(array_filter($this->storage->data()['report'], function ($item) use ($testId) {
            if ($item['user_id'] !== $this->user['id']) {
                return false;
            }
            if ($testId) {
                return $item['test_id'] === $testId;
            }
            return true;
        }));
        usort($reports, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return Response::success(['items' => $reports]);
    }

    public function reportDetail(int $id): array
    {
        $report = $this->findBy($this->storage->data()['report'], 'id', $id);
        if (!$report || $report['user_id'] !== $this->user['id']) {
            return Response::error(404, 'report not found');
        }
        return Response::success($report);
    }

    public function growthOverview(): array
    {
        $wallet = $this->findBy($this->storage->data()['user_wallet'], 'user_id', $this->user['id']);
        return Response::success(['wallet' => $wallet]);
    }

    public function growthCheckin(): array
    {
        $idem = $this->request['body']['idempotency_key'] ?? null;
        if (!$idem) {
            return Response::error(400, 'idempotency_key required');
        }
        $today = date('Y-m-d');
        $data = $this->storage->data();
        $already = $this->findBy($data['checkin_log'], 'key', $this->user['id'] . ':' . $today);
        if ($already) {
            return Response::success(new stdClass());
        }
        $this->storage->update(function (&$data) use ($idem, $today) {
            $data['checkin_log'][] = ['key' => $this->user['id'] . ':' . $today, 'created_at' => $this->now()];
            $data['points_ledger'][] = [
                'id' => $this->nextId($data, 'points_ledger'),
                'user_id' => $this->user['id'],
                'delta_points' => 5,
                'delta_xp' => 5,
                'biz_type' => 'checkin',
                'biz_id' => null,
                'idempotency_key' => $idem,
                'remark' => '每日打卡',
                'created_at' => $this->now(),
            ];
            foreach ($data['user_wallet'] as &$wallet) {
                if ($wallet['user_id'] === $this->user['id']) {
                    $wallet['points'] += 5;
                    $wallet['xp'] += 5;
                }
            }
        });
        $this->audit('growth.checkin', ['date' => $today]);
        return Response::success(new stdClass());
    }

    public function taskList(): array
    {
        $taskDefs = $this->storage->data()['task_def'];
        $this->ensureUserTasks($taskDefs);
        $tasks = array_values(array_filter($this->storage->data()['user_task'], fn ($item) => $item['user_id'] === $this->user['id']));
        $items = array_map(function ($task) use ($taskDefs) {
            $def = $this->findBy($taskDefs, 'id', $task['task_def_id']);
            return [
                'user_task_id' => $task['id'],
                'code' => $def['code'],
                'title' => $def['title'],
                'description' => $def['description'],
                'progress' => $task['progress'],
                'target' => $task['target'],
                'status' => $task['status'],
                'reward_points' => $def['reward_points'],
                'reward_xp' => $def['reward_xp'],
            ];
        }, $tasks);
        return Response::success(['items' => $items]);
    }

    public function taskClaim(int $id): array
    {
        $idem = $this->request['body']['idempotency_key'] ?? null;
        if (!$idem) {
            return Response::error(400, 'idempotency_key required');
        }
        $data = $this->storage->data();
        $task = $this->findBy($data['user_task'], 'id', $id);
        if (!$task || $task['user_id'] !== $this->user['id']) {
            return Response::error(404, 'task not found');
        }
        if ($task['status'] === 'claimed') {
            return Response::success(new stdClass());
        }
        $def = $this->findBy($data['task_def'], 'id', $task['task_def_id']);
        $reward = ['points' => $def['reward_points'], 'xp' => $def['reward_xp']];
        $this->storage->update(function (&$data) use ($id, $task, $def, $idem, $reward) {
            foreach ($data['user_task'] as &$item) {
                if ($item['id'] === $id) {
                    $item['status'] = 'claimed';
                    $item['claimed_at'] = $this->now();
                }
            }
            $data['points_ledger'][] = [
                'id' => $this->nextId($data, 'points_ledger'),
                'user_id' => $this->user['id'],
                'delta_points' => $reward['points'],
                'delta_xp' => $reward['xp'],
                'biz_type' => 'task',
                'biz_id' => $def['id'],
                'idempotency_key' => $idem,
                'remark' => '任务奖励',
                'created_at' => $this->now(),
            ];
            foreach ($data['user_wallet'] as &$wallet) {
                if ($wallet['user_id'] === $this->user['id']) {
                    $wallet['points'] += $reward['points'];
                    $wallet['xp'] += $reward['xp'];
                }
            }
        });
        $this->audit('task.claim', ['user_task_id' => $id]);
        return Response::success(new stdClass());
    }

    public function benefitStore(): array
    {
        return Response::success(['items' => $this->storage->data()['benefit_def']]);
    }

    public function benefitRedeem(): array
    {
        $payload = $this->request['body'];
        $benefitId = $payload['benefit_def_id'] ?? null;
        $idem = $payload['idempotency_key'] ?? null;
        if (!$benefitId || !$idem) {
            return Response::error(400, 'benefit_def_id and idempotency_key required');
        }
        $data = $this->storage->data();
        $existing = $this->findBy($data['redeem_order'], 'idempotency_key', $idem);
        if ($existing && $existing['user_id'] === $this->user['id']) {
            $benefit = $this->findBy($data['user_benefit'], 'id', $existing['user_benefit_id'] ?? null);
            return Response::success(['order_id' => $existing['id'], 'user_benefit' => $benefit]);
        }
        $def = $this->findBy($data['benefit_def'], 'id', (int) $benefitId);
        if (!$def) {
            return Response::error(404, 'benefit not found');
        }
        $wallet = $this->findBy($data['user_wallet'], 'user_id', $this->user['id']);
        if ($wallet['points'] < $def['cost_points']) {
            return Response::error(400, 'points not enough');
        }
        if ($def['stock'] !== null && $def['stock'] <= 0) {
            return Response::error(409, 'stock not enough');
        }
        $orderId = null;
        $userBenefit = null;
        $this->storage->update(function (&$data) use ($def, $idem, &$orderId, &$userBenefit) {
            foreach ($data['benefit_def'] as &$item) {
                if ($item['id'] === $def['id'] && $item['stock'] !== null) {
                    $item['stock'] = max(0, $item['stock'] - 1);
                }
            }
            foreach ($data['user_wallet'] as &$wallet) {
                if ($wallet['user_id'] === $this->user['id']) {
                    $wallet['points'] -= $def['cost_points'];
                }
            }
            $orderId = $this->nextId($data, 'redeem_order');
            $userBenefitId = $this->nextId($data, 'user_benefit');
            $userBenefit = [
                'id' => $userBenefitId,
                'user_id' => $this->user['id'],
                'benefit_def_id' => $def['id'],
                'status' => 'active',
                'benefit_code' => strtoupper(bin2hex(random_bytes(4))),
                'expires_at' => $def['validity_days'] ? $this->dateAdd($def['validity_days']) : null,
                'used_at' => null,
                'used_meta_json' => null,
            ];
            $data['redeem_order'][] = [
                'id' => $orderId,
                'user_id' => $this->user['id'],
                'benefit_def_id' => $def['id'],
                'cost_points' => $def['cost_points'],
                'status' => 'paid',
                'idempotency_key' => $idem,
                'user_benefit_id' => $userBenefitId,
                'created_at' => $this->now(),
            ];
            $data['user_benefit'][] = $userBenefit;
            $data['points_ledger'][] = [
                'id' => $this->nextId($data, 'points_ledger'),
                'user_id' => $this->user['id'],
                'delta_points' => -$def['cost_points'],
                'delta_xp' => 0,
                'biz_type' => 'redeem',
                'biz_id' => $def['id'],
                'idempotency_key' => $idem,
                'remark' => '权益兑换',
                'created_at' => $this->now(),
            ];
        });
        $this->audit('benefit.redeem', ['benefit_def_id' => $benefitId]);
        return Response::success(['order_id' => $orderId, 'user_benefit' => $userBenefit]);
    }

    public function myBenefits(): array
    {
        $items = array_values(array_filter($this->storage->data()['user_benefit'], fn ($item) => $item['user_id'] === $this->user['id']));
        $now = strtotime($this->now());
        foreach ($items as &$item) {
            if ($item['expires_at'] && strtotime($item['expires_at']) < $now) {
                $item['status'] = 'expired';
            }
        }
        return Response::success(['items' => $items]);
    }

    public function benefitUse(int $id): array
    {
        $idem = $this->request['body']['idempotency_key'] ?? null;
        if (!$idem) {
            return Response::error(400, 'idempotency_key required');
        }
        $userBenefit = $this->findBy($this->storage->data()['user_benefit'], 'id', $id);
        if (!$userBenefit || $userBenefit['user_id'] !== $this->user['id']) {
            return Response::error(404, 'benefit not found');
        }
        if ($userBenefit['status'] === 'used') {
            return Response::success(new stdClass());
        }
        $meta = $this->request['body']['meta'] ?? null;
        $this->storage->update(function (&$data) use ($id, $meta) {
            foreach ($data['user_benefit'] as &$item) {
                if ($item['id'] === $id) {
                    $item['status'] = 'used';
                    $item['used_at'] = $this->now();
                    $item['used_meta_json'] = $meta;
                }
            }
        });
        $this->audit('benefit.use', ['user_benefit_id' => $id]);
        return Response::success(new stdClass());
    }

    public function tagList(): array
    {
        return Response::success(['items' => $this->storage->data()['tag_def']]);
    }

    public function updateTags(): array
    {
        $tagIds = $this->request['body']['tag_ids'] ?? null;
        if (!is_array($tagIds)) {
            return Response::error(400, 'tag_ids required');
        }
        $this->storage->update(function (&$data) use ($tagIds) {
            $data['user_tag'] = array_values(array_filter($data['user_tag'], fn ($item) => $item['user_id'] !== $this->user['id']));
            foreach ($tagIds as $tagId) {
                $data['user_tag'][] = [
                    'user_id' => $this->user['id'],
                    'tag_id' => (int) $tagId,
                    'created_at' => $this->now(),
                ];
            }
        });
        $this->audit('buddy.tags.update', ['tag_ids' => $tagIds]);
        return Response::success(new stdClass());
    }

    public function buddyRecommendations(int $limit): array
    {
        $data = $this->storage->data();
        $userTags = array_values(array_filter($data['user_tag'], fn ($item) => $item['user_id'] === $this->user['id']));
        $tagIds = array_map(fn ($item) => $item['tag_id'], $userTags);
        $candidates = array_values(array_filter($data['user_profile'], function ($profile) {
            return $profile['id'] !== $this->user['id'];
        }));
        if (empty($candidates)) {
            $this->seedBuddyUsers();
            $candidates = array_values(array_filter($this->storage->data()['user_profile'], fn ($profile) => $profile['id'] !== $this->user['id']));
        }
        $items = [];
        foreach ($candidates as $profile) {
            if (count($items) >= $limit) {
                break;
            }
            $reasons = [];
            if ($tagIds) {
                $reasons[] = '共同兴趣标签匹配';
            }
            $reasons[] = '最近参与测评活跃';
            $items[] = [
                'user_id' => $profile['id'],
                'nickname' => $profile['nickname'],
                'avatar_url' => $profile['avatar_url'],
                'reasons' => $reasons,
            ];
        }
        return Response::success(['items' => $items]);
    }

    public function buddyAction(): array
    {
        $payload = $this->request['body'];
        $targetId = $payload['target_user_id'] ?? null;
        $action = $payload['action'] ?? null;
        if (!$targetId || !$action) {
            return Response::error(400, 'target_user_id and action required');
        }
        $match = false;
        $teamId = null;
        $this->storage->update(function (&$data) use ($targetId, $action, &$match, &$teamId) {
            $data['buddy_action'][] = [
                'id' => $this->nextId($data, 'buddy_action'),
                'user_id' => $this->user['id'],
                'target_user_id' => (int) $targetId,
                'action' => $action,
                'created_at' => $this->now(),
            ];
            if ($action === 'like') {
                $reverse = $this->findBuddyAction($data['buddy_action'], (int) $targetId, $this->user['id'], 'like');
                if ($reverse) {
                    $match = true;
                    $pair = [$this->user['id'], (int) $targetId];
                    sort($pair);
                    $data['buddy_match'][] = [
                        'id' => $this->nextId($data, 'buddy_match'),
                        'user_low_id' => $pair[0],
                        'user_high_id' => $pair[1],
                        'matched_at' => $this->now(),
                        'status' => 'active',
                    ];
                    $teamId = $this->nextId($data, 'team');
                    $data['team'][] = [
                        'id' => $teamId,
                        'team_type' => 'buddy',
                        'user_low_id' => $pair[0],
                        'user_high_id' => $pair[1],
                        'created_at' => $this->now(),
                    ];
                }
            }
        });
        $this->audit('buddy.action', ['target_user_id' => $targetId, 'action' => $action]);
        return Response::success(['match' => $match, 'team_id' => $teamId]);
    }

    public function buddyMatches(): array
    {
        $matches = [];
        foreach ($this->storage->data()['buddy_match'] as $match) {
            if ($match['user_low_id'] === $this->user['id'] || $match['user_high_id'] === $this->user['id']) {
                $buddyId = $match['user_low_id'] === $this->user['id'] ? $match['user_high_id'] : $match['user_low_id'];
                $profile = $this->findBy($this->storage->data()['user_profile'], 'id', $buddyId);
                $team = $this->findTeam($match['user_low_id'], $match['user_high_id']);
                $matches[] = [
                    'team_id' => $team['id'] ?? null,
                    'buddy_user_id' => $buddyId,
                    'nickname' => $profile['nickname'] ?? null,
                    'avatar_url' => $profile['avatar_url'] ?? null,
                    'matched_at' => $match['matched_at'],
                ];
            }
        }
        return Response::success(['items' => $matches]);
    }

    public function buddyReport(): array
    {
        $payload = $this->request['body'];
        $targetId = $payload['target_user_id'] ?? null;
        $reason = $payload['reason'] ?? null;
        if (!$targetId || !$reason) {
            return Response::error(400, 'target_user_id and reason required');
        }
        $this->storage->update(function (&$data) use ($targetId, $reason, $payload) {
            $data['buddy_report'][] = [
                'id' => $this->nextId($data, 'buddy_report'),
                'reporter_user_id' => $this->user['id'],
                'target_user_id' => (int) $targetId,
                'reason' => $reason,
                'detail' => $payload['detail'] ?? null,
                'status' => 'open',
                'handled_by' => null,
                'handled_at' => null,
                'created_at' => $this->now(),
            ];
        });
        $this->audit('buddy.report', ['target_user_id' => $targetId]);
        return Response::success(new stdClass());
    }

    public function leadCreate(string $type): array
    {
        $payload = $this->request['body'];
        if (empty($payload['name']) || empty($payload['phone'])) {
            return Response::error(400, 'name and phone required');
        }
        if ($type === 'appointment' && empty($payload['appointment_time'])) {
            return Response::error(400, 'appointment_time required');
        }
        $lead = null;
        $this->storage->update(function (&$data) use ($payload, $type, &$lead) {
            $leadId = $this->nextId($data, 'lead');
            $lead = [
                'id' => $leadId,
                'user_id' => $this->user['id'] ?? null,
                'lead_type' => $type,
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'grade' => $payload['grade'] ?? null,
                'class_type_id' => $payload['class_type_id'] ?? null,
                'appointment_time' => $payload['appointment_time'] ?? null,
                'source_scene' => $payload['source_scene'] ?? null,
                'follow_status' => 'new',
                'meta_json' => $payload['meta_json'] ?? null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
            $data['lead'][] = $lead;
        });
        $this->audit('lead.create', ['lead_id' => $lead['id'], 'type' => $type]);
        return Response::success(new stdClass());
    }

    public function leadList(): array
    {
        $items = array_values(array_filter($this->storage->data()['lead'], fn ($item) => $item['user_id'] === $this->user['id']));
        return Response::success(['items' => $items]);
    }

    private function pickQuestions(array $test, string $seed): array
    {
        $allQuestions = $this->storage->data()['question'];
        if ($test['mode'] === 'fixed') {
            $questions = array_values(array_filter($allQuestions, fn ($item) => $item['test_id'] === $test['id']));
        } else {
            $questions = array_values(array_filter($allQuestions, fn ($item) => $item['topic_id'] === $test['topic_id']));
        }
        $options = $this->storage->data()['option'];
        $questions = $this->seedShuffle($questions, $seed);
        $questions = array_slice($questions, 0, $test['question_count']);
        foreach ($questions as &$question) {
            $opts = array_values(array_filter($options, fn ($opt) => $opt['question_id'] === $question['id']));
            $opts = $this->seedShuffle($opts, $seed . $question['id']);
            $question['options'] = $opts;
            $question['option_order'] = array_map(fn ($opt) => $opt['id'], $opts);
        }
        return $questions;
    }

    private function scoreAttempt(array $data, array $attempt, array $answers): array
    {
        $answerMap = [];
        foreach ($answers as $answer) {
            $answerMap[$answer['question_id']] = $answer['answer'];
        }
        $items = array_values(array_filter($data['attempt_item'], fn ($item) => $item['attempt_id'] === $attempt['id']));
        $options = $data['option'];
        $questions = $data['question'];
        $total = 0;
        $dimensions = [];
        foreach ($items as $item) {
            $question = $this->findBy($questions, 'id', $item['question_id']);
            $answer = $answerMap[$item['question_id']] ?? null;
            $score = $this->scoreAnswer($options, $answer);
            $total += $score;
            if ($question['dimension_key']) {
                if (!isset($dimensions[$question['dimension_key']])) {
                    $dimensions[$question['dimension_key']] = 0;
                }
                $dimensions[$question['dimension_key']] += $score;
            }
        }
        $summary = $total >= 12 ? '你的学习状态稳定，适合挑战更高目标。' : '坚持积累即可看到明显进步。';
        $resultJson = [
            'total_score' => $total,
            'dimension_score' => $dimensions,
            'recommendation' => $total >= 12 ? '推荐加入衡辰尖子班' : '推荐加入衡辰特色班',
        ];
        $test = $this->findBy($data['test'], 'id', $attempt['test_id']);
        return [
            'summary' => $summary,
            'result_json' => $resultJson,
            'reward_points' => $test['reward_points'],
            'reward_xp' => $test['reward_xp'],
        ];
    }

    private function scoreAnswer(array $options, $answer): int
    {
        if (is_array($answer)) {
            $score = 0;
            foreach ($answer as $optId) {
                $opt = $this->findBy($options, 'id', (int) $optId);
                $score += $opt ? $opt['score'] : 0;
            }
            return $score;
        }
        if ($answer === null) {
            return 0;
        }
        $opt = $this->findBy($options, 'id', (int) $answer);
        return $opt ? $opt['score'] : 0;
    }

    private function ensureUserTasks(array $taskDefs): void
    {
        $data = $this->storage->data();
        $existing = array_filter($data['user_task'], fn ($item) => $item['user_id'] === $this->user['id']);
        if ($existing) {
            return;
        }
        $this->storage->update(function (&$data) use ($taskDefs) {
            foreach ($taskDefs as $def) {
                $data['user_task'][] = [
                    'id' => $this->nextId($data, 'user_task'),
                    'user_id' => $this->user['id'],
                    'task_def_id' => $def['id'],
                    'progress' => 1,
                    'target' => 1,
                    'status' => 'claimable',
                    'last_progress_at' => $this->now(),
                    'claimed_at' => null,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ];
            }
        });
    }

    private function seedBuddyUsers(): void
    {
        $this->storage->update(function (&$data) {
            for ($i = 0; $i < 3; $i += 1) {
                $userId = $this->nextId($data, 'user');
                $data['user_profile'][] = [
                    'id' => $userId,
                    'openid' => 'seed_' . $userId,
                    'nickname' => '衡辰搭子' . ($i + 1),
                    'avatar_url' => null,
                    'grade' => '初三',
                    'city' => '南召',
                    'privacy_settings' => new stdClass(),
                    'status' => 'active',
                ];
                $data['user_wallet'][] = [
                    'user_id' => $userId,
                    'points' => 0,
                    'xp' => 0,
                    'level' => 1,
                ];
            }
        });
    }

    private function findBy(array $items, string $key, $value): ?array
    {
        foreach ($items as $item) {
            if (($item[$key] ?? null) === $value) {
                return $item;
            }
        }
        return null;
    }

    private function findLedger(array $items, int $userId, string $bizType, string $idem): ?array
    {
        foreach ($items as $item) {
            if ($item['user_id'] === $userId && $item['biz_type'] === $bizType && $item['idempotency_key'] === $idem) {
                return $item;
            }
        }
        return null;
    }

    private function findBuddyAction(array $items, int $userId, int $targetId, string $action): ?array
    {
        foreach ($items as $item) {
            if ($item['user_id'] === $userId && $item['target_user_id'] === $targetId && $item['action'] === $action) {
                return $item;
            }
        }
        return null;
    }

    private function findTeam(int $low, int $high): ?array
    {
        foreach ($this->storage->data()['team'] as $team) {
            if ($team['user_low_id'] === $low && $team['user_high_id'] === $high) {
                return $team;
            }
        }
        return null;
    }

    private function seedShuffle(array $items, string $seed): array
    {
        $hash = crc32($seed);
        mt_srand($hash);
        $count = count($items);
        for ($i = $count - 1; $i > 0; $i -= 1) {
            $j = mt_rand(0, $i);
            $tmp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $tmp;
        }
        mt_srand();
        return $items;
    }

    private function nextId(array &$data, string $key): int
    {
        if (!isset($data['_seq'][$key])) {
            $data['_seq'][$key] = 1;
        }
        $data['_seq'][$key] += 1;
        return $data['_seq'][$key];
    }

    private function audit(string $action, array $payload): void
    {
        $this->storage->update(function (&$data) use ($action, $payload) {
            $data['audit_log'][] = [
                'action' => $action,
                'user_id' => $this->user['id'] ?? null,
                'payload' => $payload,
                'created_at' => $this->now(),
            ];
        });
    }

    private function now(): string
    {
        return date('c');
    }

    private function dateAdd(int $days): string
    {
        return date('c', strtotime('+' . $days . ' days'));
    }
}
