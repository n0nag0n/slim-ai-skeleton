<?php

declare(strict_types=1);

namespace App\Test;

use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge;
use Doctrine\DBAL\Connection;
use App\Util\MigrationFileResolver;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

class TestCase extends PHPUnitTestCase
{
    protected Connection $conn;

    protected function createApp(): App
    {
        $containerBuilder = new ContainerBuilder();
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
        return (new ServerRequestFactory())->createServerRequest($method, $path);
    }

    protected function runMigrations(): void
    {
        if (!isset($this->conn)) {
            $app = $this->createApp();
            $this->conn = $app->getContainer()->get(Connection::class);
        }

        $this->conn->executeStatement('CREATE TABLE IF NOT EXISTS _migrations (
            version VARCHAR(255) PRIMARY KEY,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $files = glob(__DIR__ . '/../migrations/*.sql');
        sort($files);

        foreach ($files as $file) {
            $version = basename($file);

            if (str_ends_with($version, '.mysql.sql') || str_ends_with($version, '.pgsql.sql')) {
                continue;
            }

            $sqlFile = MigrationFileResolver::resolve($file);
            $sql = file_get_contents($sqlFile);
            $this->conn->executeStatement($sql);
        }
    }
}
