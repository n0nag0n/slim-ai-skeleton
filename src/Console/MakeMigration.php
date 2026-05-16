<?php

namespace App\Console;

use DI\Container;

class MakeMigration implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $description = $args[0] ?? null;

        if (!$description) {
            echo "Usage: php console make:migration <description>\n";
            echo "Example: php console make:migration add_status_to_users\n";
            return 1;
        }

        $timestamp = date('Ymd_His');
        $filename = $timestamp . '_' . $description . '.sql';
        $path = dirname(__DIR__, 2) . '/migrations/' . $filename;

        $stub = <<<SQL
-- Migration: {$description}
-- Date: {$timestamp}

SQL;

        file_put_contents($path, $stub);
        echo "Created: migrations/{$filename}\n";

        return 0;
    }
}
