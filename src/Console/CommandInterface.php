<?php

namespace App\Console;

use DI\Container;

interface CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int;
}
