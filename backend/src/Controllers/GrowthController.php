<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Storage\JsonStore;
use App\Support\AuditLogger;

class GrowthController
{
    private const DAILY_POINT_LIMIT = 200;

    private JsonStore $store;
    private AuditLogger $auditLogger;

    public function __construct(JsonStore $store, AuditLogger $auditLogger)
    {
        $this->store = $store;
        $this->auditLogger = $auditLogger;
    }

    public function overview(Request $request, array $params, array $user): Response
    {
        $wallet = $this->store->find('user_wallet', fn ($row) => $row['user_id'] === $user['id']);
        return Response::ok([
            'wallet' => [
                'points' => $wallet['points'] ?? 0,
                'xp' => $wallet['xp'] ?? 0,
                'level' => $wallet['level'] ?? 1,
            ],
        ]);
    }

    public function checkin(Request $request, array $params, array $user): Response
    {
        $idempotencyKey = $request->body['idempotency_key'] ?? '';
        if ($idempotencyKey === '') {
            return Response::error(400, 'idempotency_key 必填');
        }
        $today = date('Y-m-d');
        $ledger = $this->store->all('points_ledger');
        foreach ($ledger as $row) {
            if ($row['user_id'] === $user['id'] && $row['biz_type'] === 'checkin' && str_starts_with($row['created_at'], $today)) {
                return Response::ok();
            }
        }
        $dailyPoints = $this->todayPoints($user['id']);
        $rewardPoints = min(10, max(0, self::DAILY_POINT_LIMIT - $dailyPoints));
        $rewardXp = 5;
        $this->applyLedger($user['id'], 'checkin', 0, $rewardPoints, $rewardXp, $idempotencyKey, '每日打卡');
        $this->advanceUserTask($user['id'], 'daily_checkin');
        $this->auditLogger->write('growth.checkin', [
            'user_id' => $user['id'],
            'points' => $rewardPoints,
        ]);
        return Response::ok();
    }

    public function tasks(Request $request, array $params, array $user): Response
    {
        $taskDefs = $this->store->all('task_def');
        $userTasks = $this->store->filter('user_task', fn ($row) => $row['user_id'] === $user['id']);
        $items = [];
        foreach ($userTasks as $userTask) {
            $def = $this->findById($taskDefs, $userTask['task_def_id']);
            if (!$def) {
                continue;
            }
            $items[] = [
                'user_task_id' => $userTask['id'],
                'code' => $def['code'],
                'title' => $def['title'],
                'description' => $def['description'] ?? null,
                'progress' => $userTask['progress'],
                'target' => $userTask['target'],
                'status' => $userTask['status'],
                'reward_points' => $def['reward_points'],
                'reward_xp' => $def['reward_xp'],
            ];
        }
        return Response::ok(['items' => $items]);
    }

    public function claim(Request $request, array $params, array $user): Response
    {
        $taskId = (int) ($params['id'] ?? 0);
        $idempotencyKey = $request->body['idempotency_key'] ?? '';
        if ($idempotencyKey === '') {
            return Response::error(400, 'idempotency_key 必填');
        }
        $userTasks = $this->store->all('user_task');
        $userTask = null;
        foreach ($userTasks as $row) {
            if ($row['id'] === $taskId && $row['user_id'] === $user['id']) {
                $userTask = $row;
                break;
            }
        }
        if (!$userTask) {
            return Response::error(404, '任务不存在');
        }
        if ($userTask['status'] === 'claimed') {
            return Response::ok();
        }
        if ($userTask['status'] !== 'claimable') {
            return Response::error(409, '任务未完成');
        }
        $taskDef = $this->store->find('task_def', fn ($row) => $row['id'] === $userTask['task_def_id']);
        if (!$taskDef) {
            return Response::error(404, '任务配置不存在');
        }
        $dailyPoints = $this->todayPoints($user['id']);
        $rewardPoints = min($taskDef['reward_points'], max(0, self::DAILY_POINT_LIMIT - $dailyPoints));
        $rewardXp = $taskDef['reward_xp'];
        $this->applyLedger($user['id'], 'task', $taskId, $rewardPoints, $rewardXp, $idempotencyKey, '任务奖励');

        $userTasks = array_map(function ($row) use ($taskId) {
            if ($row['id'] === $taskId) {
                $row['status'] = 'claimed';
                $row['claimed_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
            }
            return $row;
        }, $userTasks);
        $this->store->put('user_task', $userTasks);

        $this->auditLogger->write('growth.claim', [
            'user_id' => $user['id'],
            'task_id' => $taskId,
            'points' => $rewardPoints,
        ]);
        return Response::ok();
    }

    private function applyLedger(int $userId, string $bizType, int $bizId, int $points, int $xp, string $idempotencyKey, string $remark): void
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

    private function todayPoints(int $userId): int
    {
        $today = date('Y-m-d');
        $ledger = $this->store->all('points_ledger');
        $total = 0;
        foreach ($ledger as $row) {
            if ($row['user_id'] === $userId && str_starts_with($row['created_at'], $today)) {
                $total += (int) $row['delta_points'];
            }
        }
        return $total;
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

    private function findById(array $rows, int $id): ?array
    {
        foreach ($rows as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }
}
