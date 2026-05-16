<?php

namespace App\Test\Console;

use App\Console\MakeMigration;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MakeMigrationTest extends TestCase
{
    private string $createdFile = '';

    protected function tearDown(): void
    {
        if ($this->createdFile && file_exists($this->createdFile)) {
            unlink($this->createdFile);
        }
    }

    public function testCreatesMigrationFile(): void
    {
        $container = (new ContainerBuilder)->build();
        $command = new MakeMigration;

        ob_start();
        $exitCode = $command->execute(['test_migration_description'], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Created:', $output);

        preg_match('/migrations\/(\d{8}_\d{6}_test_migration_description\.sql)/', $output, $matches);
        $this->assertNotEmpty($matches);

        $root = dirname(__DIR__, 2);
        $this->createdFile = $root . '/migrations/' . $matches[1];
        $this->assertFileExists($this->createdFile);

        $contents = file_get_contents($this->createdFile);
        $this->assertStringContainsString('Migration: test_migration_description', $contents);
    }

    public function testReturnsErrorWithoutDescription(): void
    {
        $container = (new ContainerBuilder)->build();
        $command = new MakeMigration;

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Usage', $output);
    }
}
