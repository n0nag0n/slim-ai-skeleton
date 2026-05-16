<?php

use Slim\App;
use App\Controller\HomeController;
use App\Controller\HealthController;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index']);

    $app->get('/health', [HealthController::class, 'health']);
};
