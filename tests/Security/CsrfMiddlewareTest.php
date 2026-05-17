<?php

declare(strict_types=1);

namespace App\Test\Security;

use App\Security\CsrfMiddleware;
use App\Util\ArraySession;
use App\Util\Csrf;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class CsrfMiddlewareTest extends TestCase
{
    private Csrf $csrf;
    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        $this->csrf = new Csrf(new ArraySession());
        $this->middleware = new CsrfMiddleware($this->csrf);
    }

    public function testGetPassesThrough(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testValidTokenInHeaderPassesThrough(): void
    {
        $token = $this->csrf->generate();
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withHeader('X-CSRF-Token', $token);
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testValidTokenInBodyPassesThrough(): void
    {
        $token = $this->csrf->generate();
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withParsedBody(['csrf_token' => $token]);
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMissingTokenReturns403(): void
    {
        $this->csrf->generate();
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString('CSRF', $body['error']);
    }

    public function testInvalidTokenReturns403(): void
    {
        $this->csrf->generate();
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withHeader('X-CSRF-Token', 'invalid');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testExcludedPathSkipsValidation(): void
    {
        $middleware = new CsrfMiddleware($this->csrf, ['/webhook', '/public']);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/webhook/payment');
        $handler = $this->createPassthroughHandler();

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPutAndDeleteAreProtected(): void
    {
        $this->csrf->generate();
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/');
        $handler = $this->createPassthroughHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(403, $response->getStatusCode());
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
