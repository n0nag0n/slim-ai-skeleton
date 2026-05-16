<?php

namespace App\Test\Console;

use App\Console\Help;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class HelpTest extends TestCase
{
    public function testListsAllCommands(): void
    {
        $container = (new ContainerBuilder)->build();
        $command = new Help;

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('make:controller', $output);
        $this->assertStringContainsString('make:model', $output);
        $this->assertStringContainsString('make:migration', $output);
        $this->assertStringContainsString('cache:clear', $output);
        $this->assertStringContainsString('route:list', $output);
        $this->assertStringContainsString('sync-ai-instructions', $output);
        $this->assertStringContainsString('migrate', $output);
        $this->assertStringContainsString('help', $output);
    }
}
