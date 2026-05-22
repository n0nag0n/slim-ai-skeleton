<?php

declare(strict_types=1);

namespace App\Util;

class MigrationFileResolver
{
    public static function resolve(string $filePath): string
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';

        $suffix = match (true) {
            in_array($driver, ['pdo_mysql', 'pdo_mariadb'], true) => '.mysql.sql',
            $driver === 'pdo_pgsql' => '.pgsql.sql',
            default => null,
        };

        if ($suffix !== null) {
            $override = str_replace('.sql', $suffix, $filePath);
            if (file_exists($override)) {
                return $override;
            }
        }

        return $filePath;
    }
}
