<?php

namespace App\Test\Controller;

use App\Test\TestCase;

class HomeControllerTest extends TestCase
{
    public function testHomeReturnsHtml(): void
    {
        $app = $this->createApp();
        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('slim-ai-skeleton', (string) $response->getBody());
    }
}
