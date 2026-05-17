<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use App\Renderer\JsonRenderer;
use App\Model\Post;
use App\Util\SessionInterface;
use App\Util\Session;
use App\Util\Flash;
use App\Util\Csrf;
use Doctrine\DBAL\Connection;
use Slim\Views\Twig;
use Doctrine\DBAL\Configuration;
use App\Debug\DbalQueryLogger;
use App\Security\CsrfMiddleware;
use App\Security\CorsMiddleware;

return [
    SessionInterface::class => DI\autowire(Session::class),
    Session::class => DI\autowire(),
    Flash::class => DI\autowire(),
    Csrf::class => DI\autowire(),
    CsrfMiddleware::class => DI\autowire(),

    CorsMiddleware::class => function () {
        $default = 'http://localhost:8080,http://localhost:5173,http://localhost:4200,http://127.0.0.1:8080';
        return new CorsMiddleware(explode(',', $_ENV['ALLOWED_ORIGINS'] ?? $default));
    },

    Connection::class => function () {
        static $connection = null;

        if ($connection !== null) {
            return $connection;
        }

        $debug = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $driver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';
        $root = dirname(__DIR__);
        $dbPath = $_ENV['DB_PATH'] ?? '/var/database.sqlite';

        if ($dbPath !== ':memory:' && !str_starts_with($dbPath, '/')) {
            $dbPath = $root . '/' . $dbPath;
        }

        $params = match ($driver) {
            'pdo_sqlite' => [
                'driver' => 'pdo_sqlite',
                'path' => $dbPath,
            ],
            default => [
                'driver' => $driver,
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'dbname' => $_ENV['DB_NAME'] ?? 'app',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ],
        };

        if ($debug) {
            $config = new Configuration();
            $config->setMiddlewares([new DbalQueryLogger()]);
            $connection = DriverManager::getConnection($params, $config);
            return $connection;
        }

        $connection = DriverManager::getConnection($params);
        return $connection;
    },

    Twig::class => function () {
        $root = dirname(__DIR__);
        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'production';

        return Twig::create($root . '/templates', [
            'cache' => $isProduction ? $root . '/var/cache/twig' : false,
            'debug' => filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    },

    JsonRenderer::class => DI\autowire(),

    Post::class => DI\autowire(),
];
