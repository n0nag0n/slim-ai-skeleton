<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

/**
 * Interactive post-create-project setup: keep one AI agent instruction file
 * and remove the multi-tool sync machinery (consumers only need one agent).
 */
class SetupAiAgent implements CommandInterface
{
    /**
     * @var array<string, array{label: string, path: string|null}>
     */
    private const AGENTS = [
        'claude' => ['label' => 'Claude Code', 'path' => 'CLAUDE.md'],
        'copilot' => ['label' => 'GitHub Copilot', 'path' => '.github/copilot-instructions.md'],
        'gemini' => ['label' => 'Gemini', 'path' => 'GEMINI.md'],
        'cursor' => ['label' => 'Cursor', 'path' => '.cursorrules'],
        'windsurf' => ['label' => 'Windsurf', 'path' => '.windsurfrules'],
        'continue' => ['label' => 'Continue', 'path' => '.continue/rules/instructions.md'],
        'cline' => ['label' => 'Cline', 'path' => 'cline_docs/CONTEXT.md'],
        'none' => ['label' => 'AGENTS.md only (no tool-specific file)', 'path' => null],
    ];

    /** @var list<string> */
    private const ALL_TOOL_PATHS = [
        'CLAUDE.md',
        '.github/copilot-instructions.md',
        'GEMINI.md',
        '.cursorrules',
        '.windsurfrules',
        '.continue/rules/instructions.md',
        'cline_docs/CONTEXT.md',
    ];

    /** @var callable(): (string|false) */
    private $readLine;

    /**
     * @param callable(): (string|false)|null $readLine  Injectable stdin reader for tests
     * @param bool|null $interactive  Force interactive mode (null = auto-detect TTY)
     */
    public function __construct(
        private ?string $projectRoot = null,
        ?callable $readLine = null,
        private ?bool $interactive = null,
    ) {
        $this->readLine = $readLine ?? static function (): string|false {
            $line = fgets(STDIN);
            return $line === false ? false : $line;
        };
    }

    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $root = $this->projectRoot ?? dirname(__DIR__, 2);
        $choice = $this->resolveChoice($args[0] ?? null);

        if ($choice === null) {
            return 1;
        }

        $keepPath = self::AGENTS[$choice]['path'];
        $this->pruneToolFiles($root, $keepPath);
        $this->removeSyncMachinery($root);
        $this->stripSyncDocs($root, $keepPath);

        $label = self::AGENTS[$choice]['label'];
        echo "\nAI agent setup complete: {$label}\n";
        if ($keepPath !== null) {
            echo "Kept: AGENTS.md + {$keepPath}\n";
        } else {
            echo "Kept: AGENTS.md only\n";
        }
        echo "Removed: unused agent instruction files and sync-ai-instructions command\n";

        return 0;
    }

    private function resolveChoice(?string $arg): ?string
    {
        if ($arg !== null) {
            $key = strtolower($arg);
            if (!isset(self::AGENTS[$key])) {
                echo "Unknown agent: {$arg}\n";
                echo 'Valid options: ' . implode(', ', array_keys(self::AGENTS)) . "\n";
                return null;
            }
            return $key;
        }

        $env = getenv('AI_AGENT');
        if (is_string($env) && $env !== '' && isset(self::AGENTS[strtolower($env)])) {
            return strtolower($env);
        }

        if (!$this->isInteractive()) {
            echo "Non-interactive mode: keeping AGENTS.md only.\n";
            echo "Pass an agent name or set AI_AGENT to choose (e.g. claude, cursor).\n";
            return 'none';
        }

        echo "Which AI coding agent will you use?\n";
        echo "Only that agent's instruction file will be kept (plus AGENTS.md).\n";
        echo "The sync-ai-instructions command will be removed.\n\n";

        $keys = array_keys(self::AGENTS);
        foreach ($keys as $i => $key) {
            $n = $i + 1;
            echo "  {$n}) " . self::AGENTS[$key]['label'] . " [{$key}]\n";
        }
        echo "\nEnter number or name [" . count($keys) . "/none]: ";

        $line = ($this->readLine)();
        if ($line === false) {
            echo "\nNo input; keeping AGENTS.md only.\n";
            return 'none';
        }

        $input = strtolower(trim($line));
        if ($input === '') {
            return 'none';
        }

        if (ctype_digit($input)) {
            $index = (int) $input - 1;
            if (!isset($keys[$index])) {
                echo "Invalid choice: {$input}\n";
                return null;
            }
            return $keys[$index];
        }

        if (!isset(self::AGENTS[$input])) {
            echo "Unknown agent: {$input}\n";
            return null;
        }

        return $input;
    }

    private function isInteractive(): bool
    {
        if ($this->interactive !== null) {
            return $this->interactive;
        }

        if (getenv('COMPOSER_NO_INTERACTION') === '1') {
            return false;
        }

        return function_exists('stream_isatty') && defined('STDIN') && is_resource(STDIN) && stream_isatty(STDIN);
    }

    private function pruneToolFiles(string $root, ?string $keepPath): void
    {
        $agentsMd = $root . '/AGENTS.md';
        if ($keepPath !== null && is_file($agentsMd)) {
            $dest = $root . '/' . $keepPath;
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($agentsMd, $dest);
            echo "Ensured: {$keepPath}\n";
        }

        foreach (self::ALL_TOOL_PATHS as $path) {
            if ($path === $keepPath) {
                continue;
            }
            $full = $root . '/' . $path;
            if (is_file($full)) {
                unlink($full);
                echo "Removed: {$path}\n";
            }
        }

        $this->removeEmptyDirs($root, [
            '.continue/rules',
            '.continue',
            'cline_docs',
        ]);
    }

    /**
     * @param list<string> $relativeDirs  deepest-first optional
     */
    private function removeEmptyDirs(string $root, array $relativeDirs): void
    {
        foreach ($relativeDirs as $rel) {
            $dir = $root . '/' . $rel;
            if (is_dir($dir) && $this->isDirEmpty($dir)) {
                rmdir($dir);
                echo "Removed empty dir: {$rel}\n";
            }
        }
    }

    private function isDirEmpty(string $dir): bool
    {
        $items = scandir($dir);
        if ($items === false) {
            return false;
        }
        return count($items) === 2; // . and ..
    }

    private function removeSyncMachinery(string $root): void
    {
        $files = [
            'src/Console/SyncAiInstructions.php',
            'tests/Console/SyncAiInstructionsTest.php',
        ];
        foreach ($files as $rel) {
            $full = $root . '/' . $rel;
            if (is_file($full)) {
                unlink($full);
                echo "Removed: {$rel}\n";
            }
        }

        $this->stripConsoleRegistration($root);
        $this->stripComposerSyncScript($root);
        $this->stripHelpTestAssertion($root);
    }

    private function stripConsoleRegistration(string $root): void
    {
        $path = $root . '/config/console.php';
        if (!is_file($path)) {
            return;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }

        $content = str_replace("use App\\Console\\SyncAiInstructions;\n", '', $content);
        $content = preg_replace(
            "/\n    'sync-ai-instructions' => \[\n"
            . "        'class' => SyncAiInstructions::class,\n"
            . "        'description' => 'Sync AGENTS\.md to all AI config files',\n"
            . "    \],/",
            '',
            $content
        );

        if (is_string($content)) {
            file_put_contents($path, $content);
            echo "Updated: config/console.php (removed sync-ai-instructions)\n";
        }
    }

    private function stripComposerSyncScript(string $root): void
    {
        $path = $root . '/composer.json';
        if (!is_file($path)) {
            return;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['scripts']) || !is_array($decoded['scripts'])) {
            return;
        }
        if (!array_key_exists('sync-ai-instructions', $decoded['scripts'])) {
            return;
        }

        unset($decoded['scripts']['sync-ai-instructions']);
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return;
        }

        file_put_contents($path, $encoded . "\n");
        echo "Updated: composer.json (removed sync-ai-instructions script)\n";
    }

    private function stripHelpTestAssertion(string $root): void
    {
        $path = $root . '/tests/Console/HelpTest.php';
        if (!is_file($path)) {
            return;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }

        $updated = str_replace(
            "        \$this->assertStringContainsString('sync-ai-instructions', \$output);\n",
            '',
            $content
        );

        if ($updated !== $content) {
            file_put_contents($path, $updated);
            echo "Updated: tests/Console/HelpTest.php\n";
        }
    }

    private function stripSyncDocs(string $root, ?string $keepPath): void
    {
        $agentsPath = $root . '/AGENTS.md';
        if (is_file($agentsPath)) {
            $content = file_get_contents($agentsPath);
            if ($content !== false) {
                $cleaned = $this->cleanInstructionDoc($content);
                if ($cleaned !== $content) {
                    file_put_contents($agentsPath, $cleaned);
                    echo "Updated: AGENTS.md (removed sync docs)\n";
                }
                if ($keepPath !== null) {
                    $dest = $root . '/' . $keepPath;
                    copy($agentsPath, $dest);
                }
            }
        }

        $readmePath = $root . '/README.md';
        if (is_file($readmePath)) {
            $content = file_get_contents($readmePath);
            if ($content !== false) {
                $updated = preg_replace(
                    '/^\| `composer sync-ai-instructions` \|.*\n/m',
                    '',
                    $content
                );
                if (is_string($updated) && $updated !== $content) {
                    file_put_contents($readmePath, $updated);
                    echo "Updated: README.md (removed sync command)\n";
                }
            }
        }
    }

    private function cleanInstructionDoc(string $content): string
    {
        $content = preg_replace(
            '/^php console sync-ai-instructions\s+#.*\n/m',
            '',
            $content
        ) ?? $content;

        // Remove maintainer sync docs (old or current section title)
        $content = preg_replace(
            '/\n### (?:Syncing AI Configs|AI Agent Instruction Files)\n.*?(?=\n## )/s',
            "\n",
            $content
        ) ?? $content;

        return $content;
    }
}
