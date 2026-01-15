<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Storage\JsonStore;
use App\Support\AuditLogger;

class BuddyController
{
    private JsonStore $store;
    private AuditLogger $auditLogger;

    public function __construct(JsonStore $store, AuditLogger $auditLogger)
    {
        $this->store = $store;
        $this->auditLogger = $auditLogger;
    }

    public function tags(Request $request, array $params, array $user): Response
    {
        return Response::ok([
            'items' => $this->store->all('tag_def'),
        ]);
    }

    public function updateTags(Request $request, array $params, array $user): Response
    {
        $tagIds = $request->body['tag_ids'] ?? null;
        if (!is_array($tagIds)) {
            return Response::error(400, 'tag_ids 参数错误');
        }
        $allTags = $this->store->all('tag_def');
        $validTags = array_column($allTags, 'id');
        $tagIds = array_values(array_intersect($tagIds, $validTags));
        $userTags = $this->store->all('user_tag');
        $userTags = array_values(array_filter($userTags, fn ($row) => $row['user_id'] !== $user['id']));
        foreach ($tagIds as $tagId) {
            $userTags[] = [
                'user_id' => $user['id'],
                'tag_id' => $tagId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        $this->store->put('user_tag', $userTags);
        $this->auditLogger->write('buddy.tags.update', [
            'user_id' => $user['id'],
            'tag_ids' => $tagIds,
        ]);
        return Response::ok();
    }

    public function recommendations(Request $request, array $params, array $user): Response
    {
        $limit = isset($request->query['limit']) ? (int) $request->query['limit'] : 10;
        $allUsers = $this->store->all('user_profile');
        $actions = $this->store->all('buddy_action');
        $userTags = $this->store->all('user_tag');
        $myTags = array_column(array_filter($userTags, fn ($row) => $row['user_id'] === $user['id']), 'tag_id');
        $blocked = array_column(array_filter($actions, fn ($row) => $row['user_id'] === $user['id'] && $row['action'] === 'block'), 'target_user_id');
        $items = [];
        foreach ($allUsers as $candidate) {
            if ($candidate['id'] === $user['id']) {
                continue;
            }
            if (in_array($candidate['id'], $blocked, true)) {
                continue;
            }
            $candidateTags = array_column(array_filter($userTags, fn ($row) => $row['user_id'] === $candidate['id']), 'tag_id');
            $common = array_values(array_intersect($myTags, $candidateTags));
            $reasons = [];
            if (!empty($common)) {
                $names = [];
                foreach ($common as $tagId) {
                    $tag = $this->store->find('tag_def', fn ($row) => $row['id'] === $tagId);
                    if ($tag) {
                        $names[] = $tag['name'];
                    }
                }
                if ($names) {
                    $reasons[] = '共同标签：' . implode('、', array_slice($names, 0, 2));
                }
            }
            if (empty($reasons)) {
                $reasons[] = '学习节奏相近';
            }
            $items[] = [
                'user_id' => $candidate['id'],
                'nickname' => $candidate['nickname'] ?? null,
                'avatar_url' => $candidate['avatar_url'] ?? null,
                'reasons' => $reasons,
            ];
            if (count($items) >= $limit) {
                break;
            }
        }
        return Response::ok(['items' => $items]);
    }

    public function action(Request $request, array $params, array $user): Response
    {
        $targetId = (int) ($request->body['target_user_id'] ?? 0);
        $action = $request->body['action'] ?? '';
        if (!$targetId || !in_array($action, ['like', 'skip', 'block'], true)) {
            return Response::error(400, '参数错误');
        }
        $actions = $this->store->all('buddy_action');
        foreach ($actions as $row) {
            if ($row['user_id'] === $user['id'] && $row['target_user_id'] === $targetId) {
                return Response::ok(['match' => false, 'team_id' => null]);
            }
        }
        $actions[] = [
            'id' => count($actions) + 1,
            'user_id' => $user['id'],
            'target_user_id' => $targetId,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->put('buddy_action', $actions);

        $match = false;
        $teamId = null;
        if ($action === 'like') {
            $reverse = $this->store->find('buddy_action', fn ($row) => $row['user_id'] === $targetId && $row['target_user_id'] === $user['id'] && $row['action'] === 'like');
            if ($reverse) {
                $match = true;
                $pair = [min($user['id'], $targetId), max($user['id'], $targetId)];
                $matches = $this->store->all('buddy_match');
                $existing = $this->store->find('buddy_match', fn ($row) => $row['user_low_id'] === $pair[0] && $row['user_high_id'] === $pair[1]);
                if (!$existing) {
                    $matches[] = [
                        'id' => count($matches) + 1,
                        'user_low_id' => $pair[0],
                        'user_high_id' => $pair[1],
                        'matched_at' => date('Y-m-d H:i:s'),
                        'status' => 'active',
                    ];
                    $this->store->put('buddy_match', $matches);
                }
                $teams = $this->store->all('team');
                $team = $this->store->find('team', fn ($row) => $row['user_low_id'] === $pair[0] && $row['user_high_id'] === $pair[1]);
                if (!$team) {
                    $team = [
                        'id' => count($teams) + 1,
                        'team_type' => 'buddy',
                        'user_low_id' => $pair[0],
                        'user_high_id' => $pair[1],
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $teams[] = $team;
                    $this->store->put('team', $teams);
                }
                $teamId = $team['id'];
            }
        }
        $this->auditLogger->write('buddy.action', [
            'user_id' => $user['id'],
            'target_user_id' => $targetId,
            'action' => $action,
            'match' => $match,
        ]);
        return Response::ok([
            'match' => $match,
            'team_id' => $teamId,
        ]);
    }

    public function matches(Request $request, array $params, array $user): Response
    {
        $matches = $this->store->all('buddy_match');
        $items = [];
        foreach ($matches as $match) {
            if ($match['user_low_id'] !== $user['id'] && $match['user_high_id'] !== $user['id']) {
                continue;
            }
            $buddyId = $match['user_low_id'] === $user['id'] ? $match['user_high_id'] : $match['user_low_id'];
            $buddy = $this->store->find('user_profile', fn ($row) => $row['id'] === $buddyId);
            $team = $this->store->find('team', fn ($row) => $row['user_low_id'] === min($user['id'], $buddyId) && $row['user_high_id'] === max($user['id'], $buddyId));
            $items[] = [
                'team_id' => $team['id'] ?? null,
                'buddy_user_id' => $buddyId,
                'nickname' => $buddy['nickname'] ?? null,
                'avatar_url' => $buddy['avatar_url'] ?? null,
                'matched_at' => $match['matched_at'],
            ];
        }
        return Response::ok(['items' => $items]);
    }

    public function report(Request $request, array $params, array $user): Response
    {
        $targetId = (int) ($request->body['target_user_id'] ?? 0);
        $reason = $request->body['reason'] ?? '';
        if (!$targetId || $reason === '') {
            return Response::error(400, '参数错误');
        }
        $detail = $request->body['detail'] ?? null;
        $report = $this->store->insert('buddy_report', [
            'reporter_user_id' => $user['id'],
            'target_user_id' => $targetId,
            'reason' => $reason,
            'detail' => $detail,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->auditLogger->write('buddy.report', [
            'user_id' => $user['id'],
            'target_user_id' => $targetId,
            'report_id' => $report['id'],
        ]);
        return Response::ok();
    }
}
