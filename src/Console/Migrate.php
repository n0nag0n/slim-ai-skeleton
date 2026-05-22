<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;
use Doctrine\DBAL\DriverManager;
use App\Util\MigrationFileResolver;

class Migrate implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';
        $params = match ($driver) {
            'pdo_sqlite' => [
                'driver' => 'pdo_sqlite',
                'path' => $_ENV['DB_PATH'] ?? dirname(__DIR__, 2) . '/var/database.sqlite',
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

        $conn = DriverManager::getConnection($params);

        $conn->executeStatement('CREATE TABLE IF NOT EXISTS _migrations (
            version VARCHAR(255) PRIMARY KEY,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $executed = $conn->fetchFirstColumn('SELECT version FROM _migrations ORDER BY version');

        $files = glob(dirname(__DIR__, 2) . '/migrations/*.sql');
        if ($files === false) {
            $files = [];
        }
        sort($files);

        $count = 0;

        foreach ($files as $file) {
            $version = basename($file);

            // Skip driver-specific override files — the base .sql handles them
            if (str_ends_with($version, '.mysql.sql') || str_ends_with($version, '.pgsql.sql')) {
                continue;
            }

            if (in_array($version, $executed, true)) {
                echo "Skipped: {$version}\n";
                continue;
            }

            // Resolve to driver-specific override if one exists
            $sqlFile = MigrationFileResolver::resolve($file);

            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                echo "Error reading: {$version}\n";
                continue;
            }
            $conn->executeStatement($sql);

            // Track using the base filename so driver-switching doesn't re-run
            $conn->insert('_migrations', ['version' => $version]);

            if ($sqlFile !== $file) {
                echo "Migrated: {$version} (via " . basename($sqlFile) . ")\n";
            } else {
                echo "Migrated: {$version}\n";
            }
            $count++;
        }

        if ($count === 0) {
            echo "Nothing to migrate.\n";
        }

        return 0;
    }
}
