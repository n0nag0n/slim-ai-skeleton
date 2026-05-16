<?php

namespace App\Test\Console;

use App\Console\CacheClear;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class CacheClearTest extends TestCase
{
    private string $twigCache;
    private string $diCache;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $this->twigCache = $root . '/var/cache/twig';
        $this->diCache = $root . '/var/cache/container';
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->twigCache);
        $this->removeDir($this->diCache);
    }

    public function testSkipsWhenDirectoriesDontExist(): void
    {
        $container = (new ContainerBuilder)->build();
        $command = new CacheClear;

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Skipped', $output);
    }

    public function testClearsDirectories(): void
    {
        mkdir($this->twigCache, 0755, true);
        mkdir($this->diCache, 0755, true);
        file_put_contents($this->twigCache . '/test.php', 'test');
        file_put_contents($this->diCache . '/CompiledContainer.php', 'test');

        $container = (new ContainerBuilder)->build();
        $command = new CacheClear;

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Cleared', $output);
        $this->assertFileDoesNotExist($this->twigCache . '/test.php');
        $this->assertFileDoesNotExist($this->diCache . '/CompiledContainer.php');
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
