<?php

declare(strict_types=1);

namespace App\Console;

use App\Database\Seeds\SeederInterface;
use DI\Container;
use Doctrine\DBAL\Connection;

class DbSeed implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $root = dirname(__DIR__, 2);
        $seedsDir = $root . '/database/seeds';

        if (!is_dir($seedsDir)) {
            echo "No seeds directory found.\n";
            return 0;
        }

        $files = glob($seedsDir . '/*Seeder.php');
        if ($files === false || count($files) === 0) {
            echo "No seeders found.\n";
            return 0;
        }

        sort($files);

        $conn = $container->get(Connection::class);

        foreach ($files as $file) {
            $class = 'App\\Database\\Seeds\\' . basename($file, '.php');

            if (!class_exists($class)) {
                echo "Skipping {$class} - class not found.\n";
                continue;
            }

            $seeder = new $class();
            if (!$seeder instanceof SeederInterface) {
                echo "Skipping {$class} - does not implement SeederInterface.\n";
                continue;
            }

            $seeder->run($conn);
            echo "Seeded: {$class}\n";
        }

        echo "Done.\n";
        return 0;
    }
}
