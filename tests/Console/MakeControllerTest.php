<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\MakeController;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MakeControllerTest extends TestCase
{
    private string $controllerPath;
    private string $testPath;
    private string $templateDir;
    private string $name;

    protected function setUp(): void
    {
        $this->name = 'TestMake' . substr(uniqid(), -6);
        $root = dirname(__DIR__, 2);
        $this->controllerPath = $root . '/src/Controller/' . $this->name . 'Controller.php';
        $this->testPath = $root . '/tests/Controller/' . $this->name . 'ControllerTest.php';
        $this->templateDir = $root . '/templates/' . lcfirst($this->name);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->controllerPath)) {
            unlink($this->controllerPath);
        }
        if (file_exists($this->testPath)) {
            unlink($this->testPath);
        }
        if (is_dir($this->templateDir)) {
            $files = glob($this->templateDir . '/*.twig');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            rmdir($this->templateDir);
        }
    }

    public function testCreatesControllerAndTest(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeController();

        ob_start();
        $exitCode = $command->execute([$this->name], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->controllerPath);
        $this->assertFileExists($this->testPath);

        $controller = file_get_contents($this->controllerPath);
        $this->assertStringContainsString('class ' . $this->name . 'Controller', $controller);

        $test = file_get_contents($this->testPath);
        $this->assertStringContainsString('class ' . $this->name . 'ControllerTest', $test);
    }

    public function testReturnsErrorWithoutName(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeController();

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Usage', $output);
    }
}
