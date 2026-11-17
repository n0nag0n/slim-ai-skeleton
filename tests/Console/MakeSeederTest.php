<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\MakeSeeder;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MakeSeederTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/slim_test_seeder_' . uniqid();
        mkdir($this->root . '/database/seeds', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    public function testCreatesSeeder(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeSeeder($this->root);

        ob_start();
        $exitCode = $command->execute(['Widget'], $container);
        $output = ob_get_clean();

        $path = $this->root . '/database/seeds/WidgetSeeder.php';
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);
        $this->assertStringContainsString('class WidgetSeeder', (string) file_get_contents($path));
        $this->assertStringContainsString('Created: database/seeds/WidgetSeeder.php', $output);
    }

    public function testRejectsUnsafeName(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeSeeder($this->root);

        ob_start();
        $exitCode = $command->execute(['../../Probe'], $container);
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('PascalCase', $output);
        $this->assertFileDoesNotExist($this->root . '/ProbeSeeder.php');
        $this->assertFileDoesNotExist(dirname($this->root) . '/ProbeSeeder.php');
    }

    public function testReturnsErrorWithoutName(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeSeeder($this->root);

        ob_start();
        $exitCode = $command->execute([], $container);
        ob_end_clean();

        $this->assertSame(1, $exitCode);
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($path);
    }
}
