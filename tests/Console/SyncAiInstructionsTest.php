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
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testSyncsToAllTargets(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SyncAiInstructions($this->tempDir);

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);

        $expectedTargets = [
            $this->tempDir . '/CLAUDE.md',
            $this->tempDir . '/.github/copilot-instructions.md',
            $this->tempDir . '/GEMINI.md',
            $this->tempDir . '/.cursorrules',
            $this->tempDir . '/.windsurfrules',
            $this->tempDir . '/.continue/rules/instructions.md',
            $this->tempDir . '/cline_docs/CONTEXT.md',
        ];

        foreach ($expectedTargets as $target) {
            $this->assertFileExists($target);
            $this->assertStringEqualsFile($target, '# Test Instructions');
        }

        $this->assertStringContainsString('Synced: CLAUDE.md', $output);
        $this->assertStringContainsString('7 AI configs synced', $output);
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
