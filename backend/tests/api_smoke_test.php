<?php

require_once __DIR__ . '/../src/Storage.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Api.php';

$storagePath = __DIR__ . '/../storage/test-data.json';
if (file_exists($storagePath)) {
    unlink($storagePath);
}
$storage = new Storage($storagePath);

$request = [
    'method' => 'POST',
    'path' => '/api/auth/wechat-login',
    'body' => ['code' => 'test-code'],
    'query' => [],
];
$api = new Api($storage, $request, null);
$response = $api->login();
assert($response['code'] === 0);
assert(isset($response['data']['access_token']));

$profile = $response['data']['profile'];
$request = [
    'method' => 'POST',
    'path' => '/api/tests/1/start',
    'body' => [],
    'query' => [],
];
$api = new Api($storage, $request, $profile);
$start = $api->startTest(1);
assert($start['code'] === 0);
assert(count($start['data']['questions']) > 0);

$attemptId = $start['data']['attempt_id'];
$firstQuestion = $start['data']['questions'][0];
$answerPayload = [
    'idempotency_key' => 'idem-1',
    'answers' => [
        ['question_id' => $firstQuestion['question_id'], 'answer' => $firstQuestion['options'][0]['option_id']],
    ],
];
$request = [
    'method' => 'POST',
    'path' => '/api/attempts/' . $attemptId . '/submit',
    'body' => $answerPayload,
    'query' => [],
];
$api = new Api($storage, $request, $profile);
$submit = $api->submitAttempt($attemptId);
assert($submit['code'] === 0);
assert(isset($submit['data']['report']));

unlink($storagePath);

fwrite(STDOUT, "API smoke test passed\n");
