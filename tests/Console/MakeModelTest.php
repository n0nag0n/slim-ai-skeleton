<?php

namespace App\Test\Console;

use App\Console\MakeModel;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MakeModelTest extends TestCase
{
    private string $name;
    private string $modelPath = '';
    private string $testPath = '';
    private string $migrationPath = '';

    protected function setUp(): void
    {
        $this->name = 'TestMake' . substr(uniqid(), -6);
    }

    protected function tearDown(): void
    {
        foreach ([$this->modelPath, $this->testPath, $this->migrationPath] as $path) {
            if ($path && file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testCreatesModelMigrationAndTest(): void
    {
        $container = (new ContainerBuilder)->build();
        $command = new MakeModel;
        $root = dirname(__DIR__, 2);

        ob_start();
        $exitCode = $command->execute([$this->name], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);

        $this->modelPath = $root . '/src/Model/' . $this->name . '.php';
        $this->testPath = $root . '/tests/Model/' . $this->name . 'Test.php';

        $this->assertFileExists($this->modelPath);
        $this->assertFileExists($this->testPath);

        $model = file_get_contents($this->modelPath);
        $this->assertStringContainsString('class ' . $this->name, $model);
        $this->assertStringContainsString('Connection $conn', $model);

        $test = file_get_contents($this->testPath);
        $this->assertStringContainsString('class ' . $this->name . 'Test', $test);

        preg_match('/Created: migrations\/(\d{8}_\d{6}_create_.+?_table\.sql)/', $output, $matches);
        $this->assertNotEmpty($matches);
        $this->migrationPath = $root . '/migrations/' . $matches[1];
        $this->assertFileExists($this->migrationPath);

        $migration = file_get_contents($this->migrationPath);
        $this->assertStringContainsString('CREATE TABLE', $migration);
    }

    public function testReturnsErrorWithoutName(): void
    {
        $container = (new ContainerBuilder)->build();
        $command = new MakeModel;

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Usage', $output);
    }
}
