<?php

namespace App\Http;

class Request
{
    public string $method;
    public string $path;
    public array $headers;
    public array $query;
    public array $body;

    public function __construct(string $method, string $path, array $headers, array $query, array $body)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        $query = $_GET ?? [];
        $rawBody = file_get_contents('php://input');
        $body = [];
        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $path, $headers, $query, $body);
    }
}
