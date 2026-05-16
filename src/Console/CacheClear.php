<?php

namespace App\Console;

use DI\Container;

class CacheClear implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $root = dirname(__DIR__, 2);

        $paths = [
            $root . '/var/cache/twig',
            $root . '/var/cache/container',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                echo "Skipped (not found): {$path}\n";
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            $count = 0;
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                    $count++;
                }
            }

            echo "Cleared: {$path} ({$count} files)\n";
        }

        return 0;
    }
}
