<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\SetupAiAgent;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class SetupAiAgentTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/slim_test_setup_ai_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/src/Console', 0755, true);
        mkdir($this->tempDir . '/tests/Console', 0755, true);
        mkdir($this->tempDir . '/config', 0755, true);
        mkdir($this->tempDir . '/.github', 0755, true);
        mkdir($this->tempDir . '/.continue/rules', 0755, true);
        mkdir($this->tempDir . '/cline_docs', 0755, true);

        $agentsBody = <<<'MD'
# AGENTS.md -- For AI Coding Assistants

## CLI Commands

```bash
php console help
php console sync-ai-instructions    # Sync AGENTS.md to all AI configs
php console migrate
```

### Syncing AI Configs

`AGENTS.md` is the source of truth. Copies are mirrored to Claude, Copilot,
Gemini, Cursor, Windsurf, Continue, and Cline config files. After editing
`AGENTS.md` (root or nested), run:

```bash
composer sync-ai-instructions
```

## Where Context Lives

Nested files matter.
MD;

        file_put_contents($this->tempDir . '/AGENTS.md', $agentsBody);
        file_put_contents($this->tempDir . '/CLAUDE.md', $agentsBody);
        file_put_contents($this->tempDir . '/GEMINI.md', $agentsBody);
        file_put_contents($this->tempDir . '/.cursorrules', $agentsBody);
        file_put_contents($this->tempDir . '/.windsurfrules', $agentsBody);
        file_put_contents($this->tempDir . '/.github/copilot-instructions.md', $agentsBody);
        file_put_contents($this->tempDir . '/.continue/rules/instructions.md', $agentsBody);
        file_put_contents($this->tempDir . '/cline_docs/CONTEXT.md', $agentsBody);

        file_put_contents(
            $this->tempDir . '/src/Console/SyncAiInstructions.php',
            "<?php\n// stub\n"
        );
        file_put_contents(
            $this->tempDir . '/tests/Console/SyncAiInstructionsTest.php',
            "<?php\n// stub\n"
        );

        file_put_contents($this->tempDir . '/config/console.php', <<<'PHP'
<?php

declare(strict_types=1);

use App\Console\SyncAiInstructions;
use App\Console\SetupAiAgent;
use App\Console\Help;

return [
    'setup:ai-agent' => [
        'class' => SetupAiAgent::class,
        'description' => 'Pick AI agent',
    ],
    'sync-ai-instructions' => [
        'class' => SyncAiInstructions::class,
        'description' => 'Sync AGENTS.md to all AI config files',
    ],
    'help' => ['class' => Help::class, 'description' => 'Display available commands'],
];
PHP);

        file_put_contents($this->tempDir . '/composer.json', json_encode([
            'name' => 'test/app',
            'scripts' => [
                'sync-ai-instructions' => 'php console sync-ai-instructions',
                'test' => 'phpunit',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        file_put_contents($this->tempDir . '/tests/Console/HelpTest.php', <<<'PHP'
<?php

class HelpTest
{
    public function testListsAllCommands(): void
    {
        $output = '';
        $this->assertStringContainsString('make:controller', $output);
        $this->assertStringContainsString('sync-ai-instructions', $output);
        $this->assertStringContainsString('help', $output);
    }
}
PHP);

        file_put_contents($this->tempDir . '/README.md', <<<'MD'
## Commands

| Command | Description |
|---------|-------------|
| `composer test` | Run all tests |
| `composer sync-ai-instructions` | Sync AGENTS.md to AI tool config files |
| `composer migrate` | Run migrations |
MD);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testKeepsClaudeAndRemovesOthers(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SetupAiAgent($this->tempDir);

        ob_start();
        $exitCode = $command->execute(['claude'], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->tempDir . '/AGENTS.md');
        $this->assertFileExists($this->tempDir . '/CLAUDE.md');
        $this->assertFileDoesNotExist($this->tempDir . '/GEMINI.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.cursorrules');
        $this->assertFileDoesNotExist($this->tempDir . '/.windsurfrules');
        $this->assertFileDoesNotExist($this->tempDir . '/.github/copilot-instructions.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.continue/rules/instructions.md');
        $this->assertFileDoesNotExist($this->tempDir . '/cline_docs/CONTEXT.md');
        $this->assertDirectoryDoesNotExist($this->tempDir . '/cline_docs');
        $this->assertDirectoryDoesNotExist($this->tempDir . '/.continue');
        $this->assertStringContainsString('Claude Code', $output);
    }

    public function testNoneKeepsOnlyAgentsMd(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SetupAiAgent($this->tempDir);

        ob_start();
        $exitCode = $command->execute(['none'], $container);
        ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->tempDir . '/AGENTS.md');
        $this->assertFileDoesNotExist($this->tempDir . '/CLAUDE.md');
        $this->assertFileDoesNotExist($this->tempDir . '/GEMINI.md');
        $this->assertFileDoesNotExist($this->tempDir . '/.cursorrules');
    }

    public function testRemovesSyncMachinery(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SetupAiAgent($this->tempDir);

        ob_start();
        $command->execute(['cursor'], $container);
        ob_get_clean();

        $this->assertFileDoesNotExist($this->tempDir . '/src/Console/SyncAiInstructions.php');
        $this->assertFileDoesNotExist($this->tempDir . '/tests/Console/SyncAiInstructionsTest.php');
        $this->assertFileExists($this->tempDir . '/.cursorrules');

        $console = file_get_contents($this->tempDir . '/config/console.php');
        $this->assertIsString($console);
        $this->assertStringNotContainsString('SyncAiInstructions', $console);
        $this->assertStringNotContainsString('sync-ai-instructions', $console);
        $this->assertStringContainsString('setup:ai-agent', $console);

        /** @var array{scripts: array<string, string>} $composer */
        $composer = json_decode((string) file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayNotHasKey('sync-ai-instructions', $composer['scripts']);
        $this->assertArrayHasKey('test', $composer['scripts']);

        $helpTest = file_get_contents($this->tempDir . '/tests/Console/HelpTest.php');
        $this->assertIsString($helpTest);
        $this->assertStringNotContainsString('sync-ai-instructions', $helpTest);

        $agents = file_get_contents($this->tempDir . '/AGENTS.md');
        $this->assertIsString($agents);
        $this->assertStringNotContainsString('sync-ai-instructions', $agents);
        $this->assertStringNotContainsString('### Syncing AI Configs', $agents);
        $this->assertStringContainsString('## Where Context Lives', $agents);

        $readme = file_get_contents($this->tempDir . '/README.md');
        $this->assertIsString($readme);
        $this->assertStringNotContainsString('sync-ai-instructions', $readme);
        $this->assertStringContainsString('composer test', $readme);
    }

    public function testUnknownAgentFails(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SetupAiAgent($this->tempDir);

        ob_start();
        $exitCode = $command->execute(['not-a-real-agent'], $container);
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unknown agent', $output);
        $this->assertFileExists($this->tempDir . '/CLAUDE.md');
        $this->assertFileExists($this->tempDir . '/src/Console/SyncAiInstructions.php');
    }

    public function testInteractiveNumericChoice(): void
    {
        $container = (new ContainerBuilder())->build();
        // 1 = claude
        $command = new SetupAiAgent($this->tempDir, static fn (): string => "1\n", true);

        putenv('AI_AGENT');
        putenv('COMPOSER_NO_INTERACTION');

        ob_start();
        $exitCode = $command->execute([], $container);
        ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->tempDir . '/CLAUDE.md');
        $this->assertFileDoesNotExist($this->tempDir . '/GEMINI.md');
    }

    public function testNonInteractiveDefaultsToNone(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SetupAiAgent($this->tempDir, null, false);

        putenv('AI_AGENT');

        ob_start();
        $exitCode = $command->execute([], $container);
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Non-interactive', $output);
        $this->assertFileExists($this->tempDir . '/AGENTS.md');
        $this->assertFileDoesNotExist($this->tempDir . '/CLAUDE.md');
    }

    public function testEnvAiAgentChoice(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new SetupAiAgent($this->tempDir, null, false);

        putenv('AI_AGENT=gemini');
        try {
            ob_start();
            $exitCode = $command->execute([], $container);
            ob_get_clean();
        } finally {
            putenv('AI_AGENT');
        }

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->tempDir . '/GEMINI.md');
        $this->assertFileDoesNotExist($this->tempDir . '/CLAUDE.md');
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
