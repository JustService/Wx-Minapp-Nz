<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Storage\JsonStore;
use App\Support\AuditLogger;

class EvaluateController
{
    private JsonStore $store;
    private AuditLogger $auditLogger;

    public function __construct(JsonStore $store, AuditLogger $auditLogger)
    {
        $this->store = $store;
        $this->auditLogger = $auditLogger;
    }

    public function topics(Request $request, array $params, array $user): Response
    {
        return Response::ok([
            'items' => $this->store->all('topic'),
        ]);
    }

    public function tests(Request $request, array $params, array $user): Response
    {
        $topicId = isset($request->query['topic_id']) ? (int) $request->query['topic_id'] : null;
        $tests = $this->store->all('test');
        if ($topicId) {
            $tests = array_values(array_filter($tests, fn ($row) => $row['topic_id'] === $topicId));
        }
        return Response::ok(['items' => $tests]);
    }

    public function testDetail(Request $request, array $params, array $user): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $test = $this->store->find('test', fn ($row) => $row['id'] === $id);
        if (!$test) {
            return Response::error(404, '测评不存在');
        }
        return Response::ok($test);
    }

    public function start(Request $request, array $params, array $user): Response
    {
        $testId = (int) ($params['id'] ?? 0);
        $test = $this->store->find('test', fn ($row) => $row['id'] === $testId);
        if (!$test) {
            return Response::error(404, '测评不存在');
        }
        $seed = random_int(1000, 9999);
        $questions = $this->store->filter('question', function ($row) use ($test) {
            if ($test['mode'] === 'fixed') {
                return $row['test_id'] === $test['id'];
            }
            return $row['topic_id'] === $test['topic_id'];
        });
        $questions = $this->shuffleWithSeed($questions, $seed);
        $questions = array_slice($questions, 0, (int) $test['question_count']);

        $attempt = $this->store->insert('attempt', [
            'user_id' => $user['id'],
            'test_id' => $test['id'],
            'attempt_seed' => (string) $seed,
            'status' => 'draft',
            'started_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $attemptItems = $this->store->all('attempt_item');
        $payloadQuestions = [];
        foreach ($questions as $index => $question) {
            $options = $this->store->filter('option', fn ($row) => $row['question_id'] === $question['id']);
            $optionIds = array_column($options, 'id');
            $optionIds = $this->shuffleWithSeed($optionIds, $seed + $question['id']);
            $attemptItems[] = [
                'id' => $this->nextItemId($attemptItems),
                'attempt_id' => $attempt['id'],
                'question_id' => $question['id'],
                'question_order' => $index + 1,
                'option_order_json' => $optionIds,
                'answer_json' => null,
                'score' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $optionsMap = [];
            foreach ($optionIds as $optionId) {
                $option = $this->store->find('option', fn ($row) => $row['id'] === $optionId);
                if ($option) {
                    $optionsMap[] = [
                        'option_id' => $option['id'],
                        'content' => $option['content'],
                    ];
                }
            }
            $payloadQuestions[] = [
                'question_id' => $question['id'],
                'q_type' => $question['q_type'],
                'stem' => $question['stem'],
                'dimension_key' => $question['dimension_key'],
                'options' => $optionsMap,
            ];
        }
        $this->store->put('attempt_item', $attemptItems);
        $this->auditLogger->write('evaluate.start', [
            'user_id' => $user['id'],
            'attempt_id' => $attempt['id'],
            'test_id' => $test['id'],
        ]);

        return Response::ok([
            'attempt_id' => $attempt['id'],
            'attempt_seed' => $attempt['attempt_seed'],
            'questions' => $payloadQuestions,
        ]);
    }

    public function save(Request $request, array $params, array $user): Response
    {
        $attemptId = (int) ($params['id'] ?? 0);
        $answers = $request->body['answers'] ?? [];
        if (!is_array($answers)) {
            return Response::error(400, 'answers 参数错误');
        }
        $items = $this->store->all('attempt_item');
        $items = array_map(function ($item) use ($attemptId, $answers) {
            if ($item['attempt_id'] !== $attemptId) {
                return $item;
            }
            foreach ($answers as $answer) {
                if (($answer['question_id'] ?? null) === $item['question_id']) {
                    $item['answer_json'] = $answer['answer'];
                    $item['updated_at'] = date('Y-m-d H:i:s');
                }
            }
            return $item;
        }, $items);
        $this->store->put('attempt_item', $items);
        $this->auditLogger->write('evaluate.save', [
            'user_id' => $user['id'],
            'attempt_id' => $attemptId,
        ]);

        return Response::ok();
    }

    public function submit(Request $request, array $params, array $user): Response
    {
        $attemptId = (int) ($params['id'] ?? 0);
        $idempotencyKey = $request->body['idempotency_key'] ?? '';
        if ($idempotencyKey === '') {
            return Response::error(400, 'idempotency_key 必填');
        }
        $attempt = $this->store->find('attempt', fn ($row) => $row['id'] === $attemptId);
        if (!$attempt || $attempt['user_id'] !== $user['id']) {
            return Response::error(404, '测评记录不存在');
        }
        $existingReport = $this->store->find('report', fn ($row) => $row['attempt_id'] === $attemptId);
        if ($existingReport) {
            return Response::ok([
                'report' => $this->formatReport($existingReport),
                'reward' => [
                    'points' => 0,
                    'xp' => 0,
                ],
            ]);
        }

        $answers = $request->body['answers'] ?? [];
        if (!is_array($answers)) {
            return Response::error(400, 'answers 参数错误');
        }

        $items = $this->store->all('attempt_item');
        $items = array_map(function ($item) use ($attemptId, $answers) {
            if ($item['attempt_id'] !== $attemptId) {
                return $item;
            }
            foreach ($answers as $answer) {
                if (($answer['question_id'] ?? null) === $item['question_id']) {
                    $item['answer_json'] = $answer['answer'];
                }
            }
            return $item;
        }, $items);

        $totalScore = 0;
        $dimensionScores = [];
        foreach ($items as &$item) {
            if ($item['attempt_id'] !== $attemptId) {
                continue;
            }
            $score = $this->calculateScore((array) $item['answer_json']);
            $item['score'] = $score;
            $totalScore += $score;
            $question = $this->store->find('question', fn ($row) => $row['id'] === $item['question_id']);
            if ($question && $question['dimension_key']) {
                $dimensionScores[$question['dimension_key']] = ($dimensionScores[$question['dimension_key']] ?? 0) + $score;
            }
        }
        unset($item);
        $this->store->put('attempt_item', $items);

        $summary = $this->buildSummary($totalScore);
        $report = $this->store->insert('report', [
            'user_id' => $user['id'],
            'test_id' => $attempt['test_id'],
            'attempt_id' => $attempt['id'],
            'summary' => $summary,
            'result_json' => [
                'total_score' => $totalScore,
                'dimension_scores' => $dimensionScores,
                'recommendation' => $summary,
            ],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $attempts = $this->store->all('attempt');
        $attempts = array_map(function ($row) use ($attemptId, $idempotencyKey) {
            if ($row['id'] === $attemptId) {
                $row['status'] = 'submitted';
                $row['submitted_at'] = date('Y-m-d H:i:s');
                $row['submission_idempotency_key'] = $idempotencyKey;
                $row['updated_at'] = date('Y-m-d H:i:s');
            }
            return $row;
        }, $attempts);
        $this->store->put('attempt', $attempts);

        $test = $this->store->find('test', fn ($row) => $row['id'] === $attempt['test_id']);
        $rewardPoints = $test['reward_points'] ?? 0;
        $rewardXp = $test['reward_xp'] ?? 0;
        $this->applyReward($user['id'], 'test', $attemptId, $rewardPoints, $rewardXp, $idempotencyKey, '测评奖励');

        $this->advanceUserTask($user['id'], 'daily_test');
        $this->advanceUserTask($user['id'], 'first_report');

        $this->auditLogger->write('evaluate.submit', [
            'user_id' => $user['id'],
            'attempt_id' => $attemptId,
            'total_score' => $totalScore,
            'idempotency_key' => $idempotencyKey,
        ]);

        return Response::ok([
            'report' => $this->formatReport($report),
            'reward' => [
                'points' => $rewardPoints,
                'xp' => $rewardXp,
            ],
        ]);
    }

    public function reports(Request $request, array $params, array $user): Response
    {
        $reports = $this->store->filter('report', fn ($row) => $row['user_id'] === $user['id']);
        usort($reports, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return Response::ok([
            'items' => array_map([$this, 'formatReport'], $reports),
        ]);
    }

    public function reportDetail(Request $request, array $params, array $user): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $report = $this->store->find('report', fn ($row) => $row['id'] === $id && $row['user_id'] === $user['id']);
        if (!$report) {
            return Response::error(404, '报告不存在');
        }
        return Response::ok($this->formatReport($report));
    }

    private function shuffleWithSeed(array $items, int $seed): array
    {
        mt_srand($seed);
        $values = $items;
        shuffle($values);
        return $values;
    }

    private function nextItemId(array $items): int
    {
        $ids = array_column($items, 'id');
        return empty($ids) ? 1 : max($ids) + 1;
    }

    private function calculateScore(array $answer): int
    {
        $score = 0;
        $answerIds = is_array($answer) ? $answer : [$answer];
        foreach ($answerIds as $optionId) {
            $option = $this->store->find('option', fn ($row) => $row['id'] === $optionId);
            if ($option) {
                $score += (int) ($option['score'] ?? 0);
            }
        }
        return $score;
    }

    private function buildSummary(int $totalScore): string
    {
        if ($totalScore >= 12) {
            return '你的学习潜能极高，适合挑战高阶班型。';
        }
        if ($totalScore >= 8) {
            return '学习力稳步提升，保持节奏即可。';
        }
        return '建议加强基础巩固，循序渐进成长。';
    }

    private function applyReward(int $userId, string $bizType, int $bizId, int $points, int $xp, string $idempotencyKey, string $remark): void
    {
        $ledger = $this->store->all('points_ledger');
        foreach ($ledger as $row) {
            if ($row['user_id'] === $userId && $row['biz_type'] === $bizType && $row['idempotency_key'] === $idempotencyKey) {
                return;
            }
        }
        $ledger[] = [
            'id' => count($ledger) + 1,
            'user_id' => $userId,
            'delta_points' => $points,
            'delta_xp' => $xp,
            'biz_type' => $bizType,
            'biz_id' => $bizId,
            'idempotency_key' => $idempotencyKey,
            'remark' => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->put('points_ledger', $ledger);

        $wallets = $this->store->all('user_wallet');
        $wallets = array_map(function ($wallet) use ($userId, $points, $xp) {
            if ($wallet['user_id'] === $userId) {
                $wallet['points'] += $points;
                $wallet['xp'] += $xp;
                $wallet['level'] = 1 + intdiv($wallet['xp'], 200);
                $wallet['updated_at'] = date('Y-m-d H:i:s');
            }
            return $wallet;
        }, $wallets);
        $this->store->put('user_wallet', $wallets);
    }

    private function advanceUserTask(int $userId, string $code): void
    {
        $task = $this->store->find('task_def', fn ($row) => $row['code'] === $code);
        if (!$task) {
            return;
        }
        $tasks = $this->store->all('user_task');
        $tasks = array_map(function ($row) use ($userId, $task) {
            if ($row['user_id'] === $userId && $row['task_def_id'] === $task['id']) {
                $row['progress'] = $row['target'];
                $row['status'] = 'claimable';
                $row['last_progress_at'] = date('Y-m-d H:i:s');
            }
            return $row;
        }, $tasks);
        $this->store->put('user_task', $tasks);
    }

    private function formatReport(array $report): array
    {
        return [
            'id' => $report['id'],
            'test_id' => $report['test_id'],
            'attempt_id' => $report['attempt_id'],
            'summary' => $report['summary'],
            'result_json' => $report['result_json'],
            'created_at' => $report['created_at'],
        ];
    }
}
