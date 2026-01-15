<?php

use PHPUnit\Framework\TestCase;
use App\Http\Response;

class ApiResponseTest extends TestCase
{
    public function testResponseStructure(): void
    {
        $response = Response::ok(['demo' => 'value']);
        $reflection = new ReflectionClass($response);
        $payloadProperty = $reflection->getProperty('payload');
        $payloadProperty->setAccessible(true);
        $payload = $payloadProperty->getValue($response);

        $this->assertSame(0, $payload['code']);
        $this->assertSame('ok', $payload['message']);
        $this->assertSame('value', $payload['data']['demo']);
    }
}
