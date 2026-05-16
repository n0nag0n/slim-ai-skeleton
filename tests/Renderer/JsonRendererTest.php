<?php

namespace App\Test\Renderer;

use App\Renderer\JsonRenderer;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;

class JsonRendererTest extends TestCase
{
    private JsonRenderer $renderer;
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->renderer = new JsonRenderer;
        $this->factory = new ResponseFactory;
    }

    public function testRendersArrayAsJson(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->renderer->render($response, ['status' => 'ok']);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertSame('{"status":"ok"}', (string) $result->getBody());
    }

    public function testRendersString(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->renderer->render($response, 'hello');

        $this->assertSame('"hello"', (string) $result->getBody());
    }

    public function testRendersNull(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->renderer->render($response, null);

        $this->assertSame('null', (string) $result->getBody());
    }

    public function testCustomStatusCode(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->renderer->render($response, ['error' => 'not found'], 404);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRendersInteger(): void
    {
        $response = $this->factory->createResponse();
        $result = $this->renderer->render($response, 42);

        $this->assertSame('42', (string) $result->getBody());
    }
}
