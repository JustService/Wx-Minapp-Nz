<?php

class Response
{
    public static function success($data): array
    {
        return [
            'code' => 0,
            'message' => 'ok',
            'data' => $data,
        ];
    }

    public static function error(int $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => new stdClass(),
        ];
    }
}
