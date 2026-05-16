<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use App\Renderer\JsonRenderer;
use App\Model\Post;
use App\Util\Session;
use App\Util\Flash;
use Doctrine\DBAL\Connection;
use Slim\Views\Twig;
use Doctrine\DBAL\Configuration;
use App\Debug\DbalQueryLogger;

return [
    Session::class => DI\autowire(),
    Flash::class => DI\autowire(),

    Connection::class => function () {
        $debug = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $driver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';
        $root = dirname(__DIR__);

        $params = match ($driver) {
            'pdo_sqlite' => [
                'driver' => 'pdo_sqlite',
                'path' => $_ENV['DB_PATH'] ?? $root . '/var/database.sqlite',
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
            return DriverManager::getConnection($params, $config);
        }

        return DriverManager::getConnection($params);
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
