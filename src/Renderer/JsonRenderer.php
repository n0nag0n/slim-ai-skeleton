<?php

namespace App\Renderer;

use Psr\Http\Message\ResponseInterface;

class JsonRenderer
{
    public function render(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
