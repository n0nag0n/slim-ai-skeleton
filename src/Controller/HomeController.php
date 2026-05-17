<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->twig->render($response, 'home.twig', [
            'app' => 'slim-ai-skeleton',
            'version' => '1.0.0',
        ]);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, string $id): ResponseInterface
    {
        return $this->twig->render($response, 'home.twig', [
            'app' => 'slim-ai-skeleton',
            'version' => '1.0.0',
            'id' => $id,
        ]);
    }
}
