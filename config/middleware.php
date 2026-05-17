<?php

declare(strict_types=1);

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Util\SessionInterface;
use App\Util\Csrf;
use App\Security\SecurityHeadersMiddleware;
use App\Security\CorsMiddleware;

return function (App $app) {
    $container = $app->getContainer();

    if ($container === null) {
        return;
    }

    $debug = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // CORS outermost — handles OPTIONS preflight before anything else
    $app->add($container->get(CorsMiddleware::class));

    // Security headers wrap every response
    $app->add(new SecurityHeadersMiddleware($debug));

    // Session must start before CSRF and Twig
    $app->add(function ($request, $handler) use ($container) {
        $session = $container->get(SessionInterface::class);
        $session->start();

        // Inject csrf_token as a Twig global so forms can use it
        $csrf = $container->get(Csrf::class);
        $twig = $container->get(Twig::class);
        $twig->getEnvironment()->addGlobal('csrf_token', $csrf->generate());

        $response = $handler->handle($request);
        $session->save();
        return $response;
    });

    $twig = $container->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));
};
