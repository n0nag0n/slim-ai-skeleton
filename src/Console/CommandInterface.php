<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

interface CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int;
}
