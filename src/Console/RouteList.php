<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class RouteList implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $app = \DI\Bridge\Slim\Bridge::create($container);
        (require dirname(__DIR__, 2) . '/config/middleware.php')($app);
        (require dirname(__DIR__, 2) . '/config/routes.php')($app);
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();

        $routes = $app->getRouteCollector()->getRoutes();

        if (empty($routes)) {
            echo "No routes registered.\n";
            return 0;
        }

        echo "Registered Routes:\n\n";
        printf("  %-7s %s\n", "Method", "Pattern");
        echo "  ------- ---------------------------------------------\n";

        foreach ($routes as $route) {
            $methods = implode(',', $route->getMethods());
            $pattern = $route->getPattern();
            printf("  %-7s %s\n", $methods, $pattern);
        }

        return 0;
    }
}
