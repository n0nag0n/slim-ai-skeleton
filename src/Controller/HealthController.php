<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Renderer\JsonRenderer;

class HealthController
{
    public function __construct(private JsonRenderer $renderer) {}

    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderer->render($response, [
            'status' => 'ok',
            'time' => date('c'),
        ]);
    }
}
