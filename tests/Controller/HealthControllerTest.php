<?php

namespace App\Test\Controller;

use App\Test\TestCase;

class HealthControllerTest extends TestCase
{
    public function testHealthReturnsJson(): void
    {
        $app = $this->createApp();
        $request = $this->createRequest('GET', '/health');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('ok', $body['status']);
        $this->assertArrayHasKey('time', $body);
    }
}
