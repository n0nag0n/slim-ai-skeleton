<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controller\HomeController;
use App\Controller\HealthController;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/health', [HealthController::class, 'health']);

    // Grouping routes under a prefix keeps related endpoints together
    // and allows per-group middleware via ->add(Middleware::class).
    $app->group('/api', function (RouteCollectorProxy $api) {
        $api->get('/posts', [HomeController::class, 'index']);
        $api->get('/posts/{id}', [HomeController::class, 'show']);
    });

    $app->group('/admin', function (RouteCollectorProxy $admin) {
        $admin->get('/dashboard', [HomeController::class, 'index']);
    });
};
