<?php

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Util\Session;

return function (App $app) {
    $container = $app->getContainer();

    if ($container === null) {
        return;
    }

    $app->add(function ($request, $handler) use ($container) {
        $session = $container->get(Session::class);
        $session->start();
        $response = $handler->handle($request);
        $session->save();
        return $response;
    });

    $twig = $container->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));
};
