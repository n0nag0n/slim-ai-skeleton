<?php

namespace App\Console;

use DI\Container;

class Help implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $definitions = require __DIR__ . '/../../config/console.php';

        echo "Available commands:\n\n";

        foreach ($definitions as $name => $def) {
            echo "  {$name}\n";
            echo "    {$def['description']}\n\n";
        }

        return 0;
    }
}
