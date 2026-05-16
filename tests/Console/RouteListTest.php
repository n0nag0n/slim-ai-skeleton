<?php

namespace App\Test\Console;

use App\Console\RouteList;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class RouteListTest extends TestCase
{
    public function testListsRegisteredRoutes(): void
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions(dirname(__DIR__, 2) . '/config/dependencies.php');
        $container = $containerBuilder->build();

        $command = new RouteList;

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('/', $output);
        $this->assertStringContainsString('/health', $output);
        $this->assertStringContainsString('GET', $output);
    }
}
