<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Http\Request;
use App\Http\Router;
use App\Http\Response;
use App\Services\AuthService;
use App\Storage\JsonStore;
use App\Support\AuditLogger;
use App\Controllers\AuthController;
use App\Controllers\ContentController;
use App\Controllers\EvaluateController;
use App\Controllers\GrowthController;
use App\Controllers\BenefitController;
use App\Controllers\BuddyController;
use App\Controllers\LeadController;

$request = Request::capture();
$store = new JsonStore(__DIR__ . '/../storage/data.json');
$auditLogger = new AuditLogger(__DIR__ . '/../storage/audit_log.jsonl');
$authService = new AuthService($store);

$router = new Router();

$router->get('/content/home', [new ContentController($store), 'home'], true);
$router->get('/content/school', [new ContentController($store), 'school'], true);
$router->get('/classes', [new ContentController($store), 'classes'], true);
$router->get('/classes/{id}', [new ContentController($store), 'classDetail'], true);

$router->post('/auth/wechat-login', [new AuthController($store, $authService, $auditLogger), 'login'], true);

$router->get('/topics', [new EvaluateController($store, $auditLogger), 'topics']);
$router->get('/tests', [new EvaluateController($store, $auditLogger), 'tests']);
$router->get('/tests/{id}', [new EvaluateController($store, $auditLogger), 'testDetail']);
$router->post('/tests/{id}/start', [new EvaluateController($store, $auditLogger), 'start']);
$router->post('/attempts/{id}/save', [new EvaluateController($store, $auditLogger), 'save']);
$router->post('/attempts/{id}/submit', [new EvaluateController($store, $auditLogger), 'submit']);
$router->get('/reports', [new EvaluateController($store, $auditLogger), 'reports']);
$router->get('/reports/{id}', [new EvaluateController($store, $auditLogger), 'reportDetail']);

$router->get('/growth/overview', [new GrowthController($store, $auditLogger), 'overview']);
$router->post('/growth/checkin', [new GrowthController($store, $auditLogger), 'checkin']);
$router->get('/tasks', [new GrowthController($store, $auditLogger), 'tasks']);
$router->post('/tasks/{id}/claim', [new GrowthController($store, $auditLogger), 'claim']);

$router->get('/benefits/store', [new BenefitController($store, $auditLogger), 'store']);
$router->post('/benefits/redeem', [new BenefitController($store, $auditLogger), 'redeem']);
$router->get('/benefits/my', [new BenefitController($store, $auditLogger), 'my']);
$router->post('/benefits/{id}/use', [new BenefitController($store, $auditLogger), 'use']);

$router->get('/tags', [new BuddyController($store, $auditLogger), 'tags']);
$router->put('/me/tags', [new BuddyController($store, $auditLogger), 'updateTags']);
$router->get('/buddy/recommendations', [new BuddyController($store, $auditLogger), 'recommendations']);
$router->post('/buddy/action', [new BuddyController($store, $auditLogger), 'action']);
$router->get('/buddy/matches', [new BuddyController($store, $auditLogger), 'matches']);
$router->post('/buddy/report', [new BuddyController($store, $auditLogger), 'report']);

$router->post('/leads/pre-register', [new LeadController($store, $auditLogger), 'preRegister']);
$router->post('/leads/appointment', [new LeadController($store, $auditLogger), 'appointment']);
$router->get('/me/leads', [new LeadController($store, $auditLogger), 'myLeads']);

try {
    $response = $router->dispatch($request, $authService);
} catch (Throwable $exception) {
    $response = Response::error(500, '服务错误');
}

$response->send();
