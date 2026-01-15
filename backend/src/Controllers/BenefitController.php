<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Storage\JsonStore;
use App\Support\AuditLogger;

class BenefitController
{
    private JsonStore $store;
    private AuditLogger $auditLogger;

    public function __construct(JsonStore $store, AuditLogger $auditLogger)
    {
        $this->store = $store;
        $this->auditLogger = $auditLogger;
    }

    public function store(Request $request, array $params, array $user): Response
    {
        return Response::ok([
            'items' => $this->store->all('benefit_def'),
        ]);
    }

    public function redeem(Request $request, array $params, array $user): Response
    {
        $benefitId = (int) ($request->body['benefit_def_id'] ?? 0);
        $idempotencyKey = $request->body['idempotency_key'] ?? '';
        if (!$benefitId || $idempotencyKey === '') {
            return Response::error(400, '参数错误');
        }
        $existingOrder = $this->store->find('redeem_order', fn ($row) => $row['user_id'] === $user['id'] && $row['idempotency_key'] === $idempotencyKey);
        if ($existingOrder) {
            $userBenefit = $this->store->find('user_benefit', fn ($row) => $row['id'] === $existingOrder['user_benefit_id']);
            return Response::ok([
                'order_id' => $existingOrder['id'],
                'user_benefit' => $this->formatBenefit($userBenefit),
            ]);
        }
        $benefit = $this->store->find('benefit_def', fn ($row) => $row['id'] === $benefitId && $row['status'] === 'online');
        if (!$benefit) {
            return Response::error(404, '权益不存在');
        }
        $wallet = $this->store->find('user_wallet', fn ($row) => $row['user_id'] === $user['id']);
        if (($wallet['points'] ?? 0) < $benefit['cost_points']) {
            return Response::error(409, '积分不足');
        }
        if (!is_null($benefit['stock']) && $benefit['stock'] <= 0) {
            return Response::error(409, '库存不足');
        }
        $now = date('Y-m-d H:i:s');
        $expiresAt = null;
        if (!empty($benefit['validity_days'])) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $benefit['validity_days'] . ' days'));
        }
        $userBenefit = $this->store->insert('user_benefit', [
            'user_id' => $user['id'],
            'benefit_def_id' => $benefit['id'],
            'status' => 'active',
            'benefit_code' => strtoupper(bin2hex(random_bytes(4))),
            'expires_at' => $expiresAt,
            'used_at' => null,
            'used_meta_json' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $order = $this->store->insert('redeem_order', [
            'user_id' => $user['id'],
            'benefit_def_id' => $benefit['id'],
            'cost_points' => $benefit['cost_points'],
            'status' => 'paid',
            'idempotency_key' => $idempotencyKey,
            'created_at' => $now,
            'user_benefit_id' => $userBenefit['id'],
        ]);
        $this->applyPoints($user['id'], -$benefit['cost_points'], 0, 'redeem', $order['id'], $idempotencyKey);
        $this->auditLogger->write('benefit.redeem', [
            'user_id' => $user['id'],
            'benefit_def_id' => $benefitId,
            'order_id' => $order['id'],
        ]);

        return Response::ok([
            'order_id' => $order['id'],
            'user_benefit' => $this->formatBenefit($userBenefit),
        ]);
    }

    public function my(Request $request, array $params, array $user): Response
    {
        $allBenefits = $this->store->all('user_benefit');
        $allBenefits = array_map(function ($row) use ($user) {
            if ($row['user_id'] === $user['id']) {
                return $this->refreshStatus($row);
            }
            return $row;
        }, $allBenefits);
        $this->store->put('user_benefit', $allBenefits);
        $benefits = array_values(array_filter($allBenefits, fn ($row) => $row['user_id'] === $user['id']));
        return Response::ok([
            'items' => array_map([$this, 'formatBenefit'], $benefits),
        ]);
    }

    public function use(Request $request, array $params, array $user): Response
    {
        $benefitId = (int) ($params['id'] ?? 0);
        $idempotencyKey = $request->body['idempotency_key'] ?? '';
        if ($idempotencyKey === '') {
            return Response::error(400, 'idempotency_key 必填');
        }
        $benefits = $this->store->all('user_benefit');
        $updated = false;
        $benefits = array_map(function ($row) use ($benefitId, $user, $request, $idempotencyKey, &$updated) {
            if ($row['id'] === $benefitId && $row['user_id'] === $user['id']) {
                if ($row['status'] === 'used') {
                    $updated = true;
                    return $row;
                }
                $row['status'] = 'used';
                $row['used_at'] = date('Y-m-d H:i:s');
                $row['used_meta_json'] = $request->body['meta'] ?? null;
                $row['updated_at'] = date('Y-m-d H:i:s');
                $row['last_idempotency_key'] = $idempotencyKey;
                $updated = true;
            }
            return $row;
        }, $benefits);
        if (!$updated) {
            return Response::error(404, '权益券不存在');
        }
        $this->store->put('user_benefit', $benefits);
        $this->auditLogger->write('benefit.use', [
            'user_id' => $user['id'],
            'user_benefit_id' => $benefitId,
        ]);
        return Response::ok();
    }

    private function applyPoints(int $userId, int $points, int $xp, string $bizType, int $bizId, string $idempotencyKey): void
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
            'remark' => '积分兑换',
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

    private function refreshStatus(array $benefit): array
    {
        if ($benefit['status'] !== 'active' || empty($benefit['expires_at'])) {
            return $benefit;
        }
        if (strtotime($benefit['expires_at']) < time()) {
            $benefit['status'] = 'expired';
        }
        return $benefit;
    }

    private function formatBenefit(?array $benefit): ?array
    {
        if (!$benefit) {
            return null;
        }
        return [
            'id' => $benefit['id'],
            'benefit_def_id' => $benefit['benefit_def_id'],
            'status' => $benefit['status'],
            'benefit_code' => $benefit['benefit_code'],
            'expires_at' => $benefit['expires_at'],
            'used_at' => $benefit['used_at'],
        ];
    }
}
