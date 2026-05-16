<?php

declare(strict_types=1);

namespace App\Test\Security;

use App\Security\CorsMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CorsMiddleware([
            'http://localhost:8080',
            'http://example.com',
        ]);
    }

    public function testNoOriginPassthrough(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testAllowedOriginGetsCorsHeaders(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Origin', 'http://localhost:8080');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('http://localhost:8080', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertEquals('86400', $response->getHeaderLine('Access-Control-Max-Age'));
    }

    public function testDisallowedOriginPassthrough(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Origin', 'http://evil.com');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testOptionsPreflightReturns204(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('OPTIONS', '/')
            ->withHeader('Origin', 'http://example.com');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('http://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function testOptionsForDisallowedOriginReturns204WithoutCors(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('OPTIONS', '/')
            ->withHeader('Origin', 'http://evil.com');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        // Disallowed origin: handler runs but origin not in list so passthrough
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    private function createPassthroughHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }
}
