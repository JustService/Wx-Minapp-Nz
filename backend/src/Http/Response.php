<?php

namespace App\Http;

class Response
{
    private int $status;
    private array $payload;

    public function __construct(int $status, array $payload)
    {
        $this->status = $status;
        $this->payload = $payload;
    }

    public static function ok(array $data = []): self
    {
        return new self(200, [
            'code' => 0,
            'message' => 'ok',
            'data' => $data,
        ]);
    }

    public static function error(int $status, string $message): self
    {
        return new self($status, [
            'code' => $status,
            'message' => $message,
            'data' => new \stdClass(),
        ]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
