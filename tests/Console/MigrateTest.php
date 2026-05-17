<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\Migrate;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MigrateTest extends TestCase
{
    private string $dbPath;
    private string $origDbPath;
    private string $origDbDriver;
    private string $tempMigration;

    protected function setUp(): void
    {
        $this->origDbPath = $_ENV['DB_PATH'] ?? ':memory:';
        $this->origDbDriver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';
        $this->dbPath = sys_get_temp_dir() . '/slim_test_migrate_' . uniqid() . '.sqlite';
        $_ENV['DB_DRIVER'] = 'pdo_sqlite';
        $_ENV['DB_PATH'] = $this->dbPath;

        $this->tempMigration = dirname(__DIR__, 2) . '/migrations/0000_00_00_000000_test.sql';
        file_put_contents($this->tempMigration, 'CREATE TABLE test_migration (id INTEGER);');
    }

    protected function tearDown(): void
    {
        $_ENV['DB_PATH'] = $this->origDbPath;
        $_ENV['DB_DRIVER'] = $this->origDbDriver;
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        if (file_exists($this->tempMigration)) {
            unlink($this->tempMigration);
        }
    }

    public function testRunsPendingMigrations(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new Migrate();

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Migrated:', $output);
    }

    public function testNothingToMigrateOnSecondRun(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new Migrate();

        ob_start();
        $command->execute([], $container);
        ob_get_clean();

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Nothing to migrate', $output);
    }
}
