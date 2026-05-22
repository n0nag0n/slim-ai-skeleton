<?php

declare(strict_types=1);

namespace App\Security;

use App\Util\Csrf;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    /** @param array<int, string> $excludedPaths */
    public function __construct(
        private Csrf $csrf,
        private array $excludedPaths = [],
        private bool $debug = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        // Debug-only bypass: X-Dev: 1 skips CSRF validation
        $devHeader = $request->getHeaderLine('X-Dev');
        if ($this->debug && $devHeader === '1') {
            return $handler->handle($request);
        }

        foreach ($this->excludedPaths as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return $handler->handle($request);
            }
        }

        $token = $request->getHeaderLine('X-CSRF-Token');

        if ($token === '') {
            $parsed = $request->getParsedBody();
            $token = is_array($parsed) ? ($parsed['csrf_token'] ?? '') : '';
        }

        if ($token === '') {
            $contentType = $request->getHeaderLine('Content-Type');
            if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                $body = (string) $request->getBody();
                parse_str($body, $parsed);
                $token = $parsed['csrf_token'] ?? '';
            }
        }

        if ($token === '' || !$this->csrf->validate($token)) {
            $body = json_encode([
                'error' => 'Invalid or missing CSRF token.',
            ], JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                $body = '{"error":"Invalid or missing CSRF token."}';
            }
            $response = new \Slim\Psr7\Response(403);
            $response->getBody()->write($body);
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
