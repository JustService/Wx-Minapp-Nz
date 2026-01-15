<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Storage\JsonStore;
use App\Support\AuditLogger;

class LeadController
{
    private JsonStore $store;
    private AuditLogger $auditLogger;

    public function __construct(JsonStore $store, AuditLogger $auditLogger)
    {
        $this->store = $store;
        $this->auditLogger = $auditLogger;
    }

    public function preRegister(Request $request, array $params, array $user): Response
    {
        return $this->createLead($request, $user, 'pre_register');
    }

    public function appointment(Request $request, array $params, array $user): Response
    {
        return $this->createLead($request, $user, 'appointment');
    }

    public function myLeads(Request $request, array $params, array $user): Response
    {
        $leads = $this->store->filter('lead', fn ($row) => $row['user_id'] === $user['id']);
        return Response::ok([
            'items' => array_map(function ($lead) {
                return [
                    'id' => $lead['id'],
                    'lead_type' => $lead['lead_type'],
                    'name' => $lead['name'],
                    'phone' => $lead['phone'],
                    'grade' => $lead['grade'],
                    'class_type_id' => $lead['class_type_id'],
                    'appointment_time' => $lead['appointment_time'],
                    'follow_status' => $lead['follow_status'],
                    'created_at' => $lead['created_at'],
                ];
            }, $leads),
        ]);
    }

    private function createLead(Request $request, array $user, string $type): Response
    {
        $name = $request->body['name'] ?? '';
        $phone = $request->body['phone'] ?? '';
        if ($name === '' || $phone === '') {
            return Response::error(400, '姓名和手机号必填');
        }
        $appointment = $type === 'appointment' ? ($request->body['appointment_time'] ?? null) : null;
        if ($type === 'appointment' && !$appointment) {
            return Response::error(400, '预约时间必填');
        }
        $lead = $this->store->insert('lead', [
            'user_id' => $user['id'],
            'lead_type' => $type,
            'name' => $name,
            'phone' => $phone,
            'grade' => $request->body['grade'] ?? null,
            'class_type_id' => $request->body['class_type_id'] ?? null,
            'appointment_time' => $appointment,
            'source_scene' => $this->latestScene($user['id']),
            'follow_status' => 'new',
            'meta_json' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->auditLogger->write('lead.create', [
            'user_id' => $user['id'],
            'lead_id' => $lead['id'],
            'type' => $type,
        ]);
        return Response::ok();
    }

    private function latestScene(int $userId): ?string
    {
        $sessions = $this->store->all('sessions');
        $scene = null;
        foreach (array_reverse($sessions) as $session) {
            if ($session['user_id'] === $userId && !empty($session['scene'])) {
                $scene = $session['scene'];
                break;
            }
        }
        return $scene;
    }
}
