<?php

namespace App\Support;

class AuditLogger
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function write(string $action, array $payload): void
    {
        $record = [
            'action' => $action,
            'payload' => $payload,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->path, json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }
}
