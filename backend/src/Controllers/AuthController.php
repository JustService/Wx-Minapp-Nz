<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Storage\JsonStore;
use App\Support\AuditLogger;

class AuthController
{
    private JsonStore $store;
    private AuthService $authService;
    private AuditLogger $auditLogger;

    public function __construct(JsonStore $store, AuthService $authService, AuditLogger $auditLogger)
    {
        $this->store = $store;
        $this->authService = $authService;
        $this->auditLogger = $auditLogger;
    }

    public function login(Request $request): Response
    {
        $code = $request->body['code'] ?? '';
        if ($code === '') {
            return Response::error(400, 'code 必填');
        }
        $scene = $request->body['scene'] ?? null;
        $openid = 'wx_' . substr(sha1($code), 0, 20);
        $user = $this->store->find('user_profile', fn ($row) => $row['openid'] === $openid);
        if (!$user) {
            $user = $this->store->insert('user_profile', [
                'openid' => $openid,
                'nickname' => '新同学',
                'avatar_url' => null,
                'grade' => null,
                'city' => null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $wallets = $this->store->all('user_wallet');
            $wallets[] = [
                'user_id' => $user['id'],
                'points' => 0,
                'xp' => 0,
                'level' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->store->put('user_wallet', $wallets);
        }
        $token = $this->authService->createSession((int) $user['id']);
        if ($scene) {
            $sessions = $this->store->all('sessions');
            $sessions = array_map(function ($row) use ($token, $scene) {
                if ($row['token'] === $token) {
                    $row['scene'] = $scene;
                }
                return $row;
            }, $sessions);
            $this->store->put('sessions', $sessions);
        }
        $wallet = $this->store->find('user_wallet', fn ($row) => $row['user_id'] === $user['id']);
        $this->auditLogger->write('auth.login', [
            'user_id' => $user['id'],
            'scene' => $scene,
        ]);

        return Response::ok([
            'access_token' => $token,
            'profile' => [
                'id' => $user['id'],
                'openid' => $user['openid'],
                'nickname' => $user['nickname'] ?? null,
                'avatar_url' => $user['avatar_url'] ?? null,
                'grade' => $user['grade'] ?? null,
                'city' => $user['city'] ?? null,
                'status' => $user['status'] ?? 'active',
            ],
            'wallet' => [
                'points' => $wallet['points'] ?? 0,
                'xp' => $wallet['xp'] ?? 0,
                'level' => $wallet['level'] ?? 1,
            ],
        ]);
    }
}
