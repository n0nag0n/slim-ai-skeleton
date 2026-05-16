<?php

namespace App\Test;

use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

class TestCase extends PHPUnitTestCase
{
    protected function createApp(): App
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions(__DIR__ . '/../config/dependencies.php');
        $container = $containerBuilder->build();

        $app = Bridge::create($container);

        (require __DIR__ . '/../config/middleware.php')($app);
        (require __DIR__ . '/../config/routes.php')($app);

        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();

        return $app;
    }

    protected function createRequest(string $method, string $path): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory)->createServerRequest($method, $path);
    }
}
