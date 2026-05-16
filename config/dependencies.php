<?php

use Doctrine\DBAL\DriverManager;
use App\Renderer\JsonRenderer;
use App\Model\Post;
use Doctrine\DBAL\Connection;
use Slim\Views\Twig;

return [
    Connection::class => function () {
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
