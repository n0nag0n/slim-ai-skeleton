<?php

namespace App\Renderer;

use Psr\Http\Message\ResponseInterface;

class JsonRenderer
{
    public function render(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $json = json_encode($data);
        if ($json === false) {
            $json = 'null';
        }
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
