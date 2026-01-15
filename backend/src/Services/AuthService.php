<?php

namespace App\Services;

use App\Http\Request;
use App\Storage\JsonStore;

class AuthService
{
    private JsonStore $store;

    public function __construct(JsonStore $store)
    {
        $this->store = $store;
    }

    public function authenticate(Request $request): ?array
    {
        $auth = $request->headers['authorization'] ?? '';
        if (!str_starts_with($auth, 'Bearer ')) {
            return null;
        }
        $token = trim(substr($auth, 7));
        if ($token === '') {
            return null;
        }
        $session = $this->store->find('sessions', fn ($row) => $row['token'] === $token);
        if (!$session) {
            return null;
        }
        $user = $this->store->find('user_profile', fn ($row) => $row['id'] === $session['user_id']);
        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            return null;
        }

        return $user;
    }

    public function createSession(int $userId): string
    {
        $token = base64_encode(hash('sha256', $userId . '|' . microtime(true), true));
        $sessions = $this->store->all('sessions');
        $sessions[] = [
            'token' => $token,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->put('sessions', $sessions);

        return $token;
    }
}
