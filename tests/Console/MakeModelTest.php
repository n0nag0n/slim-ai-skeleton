<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\MakeModel;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MakeModelTest extends TestCase
{
    private string $name;
    private string $modelPath = '';
    private string $testPath = '';

    protected function setUp(): void
    {
        $this->name = 'TestMake' . substr(uniqid(), -6);
    }

    protected function tearDown(): void
    {
        foreach ([$this->modelPath, $this->testPath] as $path) {
            if ($path && file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testCreatesModelAndTest(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeModel();
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
        $this->assertStringContainsString('declare(strict_types=1)', $model);

        $test = file_get_contents($this->testPath);
        $this->assertStringContainsString('class ' . $this->name . 'Test', $test);
        $this->assertStringContainsString('declare(strict_types=1)', $test);

        $this->assertStringContainsString('testFindAll', $test);
        $this->assertStringContainsString('testFindById', $test);
        $this->assertStringContainsString('testCreate', $test);
        $this->assertStringContainsString('testUpdate', $test);
        $this->assertStringContainsString('testDelete', $test);

        $this->assertStringContainsString('make:migration', $output);
    }

    public function testReturnsErrorWithoutName(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeModel();

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Usage', $output);
    }
}
