<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\SelectAiProvider;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class SelectAiProviderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/slim_test_provider_' . uniqid();
        mkdir($this->tempDir . '/.github', 0755, true);
        mkdir($this->tempDir . '/cline_docs', 0755, true);
        mkdir($this->tempDir . '/.agents/skills/add-html-page', 0755, true);

        file_put_contents($this->tempDir . '/AGENTS.md', '# Rules');
        file_put_contents($this->tempDir . '/CLAUDE.md', '# Claude pointer');
        file_put_contents($this->tempDir . '/GEMINI.md', '# Gemini');
        file_put_contents($this->tempDir . '/.cursorrules', '# Cursor pointer');
        file_put_contents($this->tempDir . '/.windsurfrules', '# Windsurf');
        file_put_contents($this->tempDir . '/.github/copilot-instructions.md', '# Copilot pointer');
        file_put_contents($this->tempDir . '/.github/workflows.yml', "name: ci\n");
        file_put_contents($this->tempDir . '/cline_docs/CONTEXT.md', '# Cline');
        file_put_contents($this->tempDir . '/.agents/skills/add-html-page/SKILL.md', '# Skill');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testClaudeKeepsItsPointerAndLinksSkills(): void
    {
        $this->runProvider('claude');

        $this->assertFileExists($this->tempDir . '/AGENTS.md');
        $this->assertFileExists($this->tempDir . '/CLAUDE.md');
        $this->assertFileExists($this->tempDir . '/.agents/skills/add-html-page/SKILL.md');
        $this->assertFileDoesNotExist($this->tempDir . '/GEMINI.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.cursorrules');
        $this->assertFileDoesNotExist($this->tempDir . '/cline_docs');

        $link = $this->tempDir . '/.claude/skills/add-html-page';
        $this->assertTrue(is_link($link));
        $this->assertSame('../../.agents/skills/add-html-page', readlink($link));
    }

    public function testSwitchingProviderRemovesSkillLinks(): void
    {
        $this->runProvider('claude');
        $link = $this->tempDir . '/.claude/skills/add-html-page';
        $this->assertTrue(is_link($link));

        $this->runProvider('grok');

        $this->assertFileDoesNotExist($link);
        $this->assertFileDoesNotExist($this->tempDir . '/.claude');
        $this->assertStringEqualsFile($this->tempDir . '/.agents/provider', "grok\n");
    }

    public function testCursorKeepsPointerWithoutSkillLinks(): void
    {
        $this->runProvider('cursor');

        $this->assertFileExists($this->tempDir . '/.cursorrules');
        $this->assertFileDoesNotExist($this->tempDir . '/CLAUDE.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.cursor');
        $this->assertStringEqualsFile($this->tempDir . '/.agents/provider', "cursor\n");
    }

    public function testGrokRemovesOtherInstructionFiles(): void
    {
        $this->runProvider('grok');

        $this->assertFileExists($this->tempDir . '/AGENTS.md');
        $this->assertFileExists($this->tempDir . '/.agents/skills/add-html-page/SKILL.md');
        $this->assertFileDoesNotExist($this->tempDir . '/CLAUDE.md');
        $this->assertFileDoesNotExist($this->tempDir . '/GEMINI.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.cursorrules');
        $this->assertFileDoesNotExist($this->tempDir . '/.windsurfrules');
        $this->assertFileDoesNotExist($this->tempDir . '/.github/copilot-instructions.md');
        $this->assertDirectoryExists($this->tempDir . '/.github');
    }

    public function testAllKeepsEveryFile(): void
    {
        $this->runProvider('all');

        $this->assertFileExists($this->tempDir . '/CLAUDE.md');
        $this->assertFileExists($this->tempDir . '/GEMINI.md');
        $this->assertFileExists($this->tempDir . '/cline_docs/CONTEXT.md');
        $this->assertStringEqualsFile($this->tempDir . '/.agents/provider', "all\n");
    }

    public function testUnknownProviderFails(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SelectAiProvider($this->tempDir);

        ob_start();
        $exitCode = $command->execute(['notepad'], $container);
        ob_end_clean();

        $this->assertSame(1, $exitCode);
        $this->assertFileExists($this->tempDir . '/CLAUDE.md');
    }

    private function runProvider(string $name): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SelectAiProvider($this->tempDir);

        ob_start();
        $exitCode = $command->execute([$name], $container);
        ob_end_clean();

        $this->assertSame(0, $exitCode);
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
            if ($file->isLink()) {
                unlink($file->getPathname());
                continue;
            }
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($path);
    }
}
