<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Renderer\JsonRenderer;
use Doctrine\DBAL\Connection;

class HealthController
{
    public function __construct(
        private JsonRenderer $renderer,
        private Connection $conn
    ) {
    }

    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $checks = [];
        $overall = 'ok';

        // Lightweight DB connectivity check (works on SQLite, MySQL, Postgres)
        try {
            $this->conn->fetchOne('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $overall = 'degraded';
        }

        return $this->renderer->render($response, [
            'status' => $overall,
            'time' => date('c'),
            'checks' => $checks,
        ]);
    }
}
