<?php

declare(strict_types=1);

namespace App\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private bool $debug = false)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $scriptSrc = $this->debug ? "'self' 'unsafe-inline'" : "'self'";

        $csp = "default-src 'self'; script-src {$scriptSrc};"
            . " style-src 'self' 'unsafe-inline';"
            . " img-src 'self' data:; font-src 'self';"
            . " form-action 'self'; frame-ancestors 'none'";

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '0')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
