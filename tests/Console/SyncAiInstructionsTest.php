<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\SyncAiInstructions;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class SyncAiInstructionsTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/slim_test_sync_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        file_put_contents($this->tempDir . '/AGENTS.md', '# Test Instructions');
        file_put_contents($this->tempDir . '/CLAUDE.md', '# Pointer');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testSyncsFullCopiesAndLeavesPointers(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SyncAiInstructions($this->tempDir);

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);

        $fullCopies = [
            $this->tempDir . '/GEMINI.md',
            $this->tempDir . '/.windsurfrules',
            $this->tempDir . '/cline_docs/CONTEXT.md',
        ];

        foreach ($fullCopies as $target) {
            $this->assertFileExists($target);
            $this->assertStringEqualsFile($target, '# Test Instructions');
        }

        $this->assertStringEqualsFile($this->tempDir . '/CLAUDE.md', '# Pointer');
        $this->assertFileDoesNotExist($this->tempDir . '/.cursorrules');
        $this->assertStringContainsString('Synced: GEMINI.md', $output);
        $this->assertStringContainsString('3 AI configs synced', $output);
    }

    public function testSkipsCopiesTheSelectedProviderDoesNotKeep(): void
    {
        mkdir($this->tempDir . '/.agents', 0755, true);
        file_put_contents($this->tempDir . '/.agents/provider', "claude\n");

        $container = (new ContainerBuilder())->build();
        $command = new SyncAiInstructions($this->tempDir);

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileDoesNotExist($this->tempDir . '/GEMINI.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.windsurfrules');
        $this->assertFileDoesNotExist($this->tempDir . '/cline_docs');
        $this->assertStringContainsString('Skipped: GEMINI.md', $output);
        $this->assertStringContainsString('0 AI configs synced', $output);
    }

    public function testSyncsOnlyTheSavedProviderCopy(): void
    {
        mkdir($this->tempDir . '/.agents', 0755, true);
        file_put_contents($this->tempDir . '/.agents/provider', "gemini\n");

        $container = (new ContainerBuilder())->build();
        $command = new SyncAiInstructions($this->tempDir);

        ob_start();
        $exitCode = $command->execute([], $container);
        ob_end_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringEqualsFile($this->tempDir . '/GEMINI.md', '# Test Instructions');
        $this->assertFileDoesNotExist($this->tempDir . '/.windsurfrules');
        $this->assertFileDoesNotExist($this->tempDir . '/cline_docs');
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
