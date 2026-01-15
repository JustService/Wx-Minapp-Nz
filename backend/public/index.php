<?php

require_once __DIR__ . '/../src/Storage.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Api.php';

$storage = new Storage(__DIR__ . '/../storage/data.json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$input = file_get_contents('php://input');
$body = $input ? json_decode($input, true) : [];
$headers = getallheaders();

$publicPaths = [
    'POST /api/auth/wechat-login',
    'GET /api/content/home',
    'GET /api/content/school',
    'GET /api/classes',
    'GET /api/classes/',
];

$user = null;
if (!in_array($method . ' ' . $path, $publicPaths, true) && !preg_match('#^/api/classes/\d+$#', $path)) {
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if ($auth && str_starts_with($auth, 'Bearer ')) {
        $token = substr($auth, 7);
        $userId = $storage->data()['tokens'][$token] ?? null;
        if ($userId) {
            $user = null;
            foreach ($storage->data()['user_profile'] as $profile) {
                if ($profile['id'] === $userId) {
                    $user = $profile;
                    break;
                }
            }
        }
    }
    if (!$user) {
        respond(Response::error(401, 'unauthorized'));
    }
}

$request = [
    'method' => $method,
    'path' => $path,
    'body' => is_array($body) ? $body : [],
    'query' => $_GET,
];

$api = new Api($storage, $request, $user);

switch (true) {
    case $method === 'POST' && $path === '/api/auth/wechat-login':
        respond($api->login());
        break;
    case $method === 'GET' && $path === '/api/content/home':
        respond($api->contentHome());
        break;
    case $method === 'GET' && $path === '/api/content/school':
        respond($api->contentSchool());
        break;
    case $method === 'GET' && $path === '/api/classes':
        respond($api->classList());
        break;
    case $method === 'GET' && preg_match('#^/api/classes/(\d+)$#', $path, $matches):
        respond($api->classDetail((int) $matches[1]));
        break;
    case $method === 'GET' && $path === '/api/topics':
        respond($api->topicList());
        break;
    case $method === 'GET' && $path === '/api/tests':
        $topicId = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : null;
        respond($api->testList($topicId));
        break;
    case $method === 'GET' && preg_match('#^/api/tests/(\d+)$#', $path, $matches):
        respond($api->testDetail((int) $matches[1]));
        break;
    case $method === 'POST' && preg_match('#^/api/tests/(\d+)/start$#', $path, $matches):
        respond($api->startTest((int) $matches[1]));
        break;
    case $method === 'POST' && preg_match('#^/api/attempts/(\d+)/save$#', $path, $matches):
        respond($api->saveAttempt((int) $matches[1]));
        break;
    case $method === 'POST' && preg_match('#^/api/attempts/(\d+)/submit$#', $path, $matches):
        respond($api->submitAttempt((int) $matches[1]));
        break;
    case $method === 'GET' && $path === '/api/reports':
        $testId = isset($_GET['test_id']) ? (int) $_GET['test_id'] : null;
        respond($api->reportList($testId));
        break;
    case $method === 'GET' && preg_match('#^/api/reports/(\d+)$#', $path, $matches):
        respond($api->reportDetail((int) $matches[1]));
        break;
    case $method === 'GET' && $path === '/api/growth/overview':
        respond($api->growthOverview());
        break;
    case $method === 'POST' && $path === '/api/growth/checkin':
        respond($api->growthCheckin());
        break;
    case $method === 'GET' && $path === '/api/tasks':
        respond($api->taskList());
        break;
    case $method === 'POST' && preg_match('#^/api/tasks/(\d+)/claim$#', $path, $matches):
        respond($api->taskClaim((int) $matches[1]));
        break;
    case $method === 'GET' && $path === '/api/benefits/store':
        respond($api->benefitStore());
        break;
    case $method === 'POST' && $path === '/api/benefits/redeem':
        respond($api->benefitRedeem());
        break;
    case $method === 'GET' && $path === '/api/benefits/my':
        respond($api->myBenefits());
        break;
    case $method === 'POST' && preg_match('#^/api/benefits/(\d+)/use$#', $path, $matches):
        respond($api->benefitUse((int) $matches[1]));
        break;
    case $method === 'GET' && $path === '/api/tags':
        respond($api->tagList());
        break;
    case $method === 'PUT' && $path === '/api/me/tags':
        respond($api->updateTags());
        break;
    case $method === 'GET' && $path === '/api/buddy/recommendations':
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        respond($api->buddyRecommendations($limit));
        break;
    case $method === 'POST' && $path === '/api/buddy/action':
        respond($api->buddyAction());
        break;
    case $method === 'GET' && $path === '/api/buddy/matches':
        respond($api->buddyMatches());
        break;
    case $method === 'POST' && $path === '/api/buddy/report':
        respond($api->buddyReport());
        break;
    case $method === 'POST' && $path === '/api/leads/pre-register':
        respond($api->leadCreate('pre_register'));
        break;
    case $method === 'POST' && $path === '/api/leads/appointment':
        respond($api->leadCreate('appointment'));
        break;
    case $method === 'GET' && $path === '/api/me/leads':
        respond($api->leadList());
        break;
    default:
        respond(Response::error(404, 'not found'));
}

function respond(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
