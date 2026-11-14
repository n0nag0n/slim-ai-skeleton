<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class SyncAiInstructions implements CommandInterface
{
    /**
     * Full copies for tools that still look for these filenames.
     * The value is the provider that keeps that file.
     *
     * @var array<string, string>
     */
    private array $fullCopies = [
        'GEMINI.md' => 'gemini',
        '.windsurfrules' => 'windsurf',
        'cline_docs/CONTEXT.md' => 'cline',
    ];

    /** @var array<int, string> */
    private array $knownProviders = [
        'grok',
        'codex',
        'claude',
        'cursor',
        'copilot',
        'gemini',
        'windsurf',
        'cline',
    ];

    public function __construct(private ?string $projectRoot = null)
    {
    }

    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $root = $this->projectRoot ?? dirname(__DIR__, 2);
        $source = $root . '/AGENTS.md';
        $provider = $this->readProvider($root);
        if ($provider === false) {
            return 1;
        }

        $count = 0;
        foreach ($this->fullCopies as $target => $owner) {
            if ($provider !== null && $provider !== $owner) {
                echo "Skipped: {$target}\n";
                continue;
            }

            $dest = $root . '/' . $target;
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($source, $dest);
            echo "Synced: {$target}\n";
            $count++;
        }

        echo "\n{$count} AI configs synced from AGENTS.md\n";
        echo "Pointers left unchanged: CLAUDE.md, .cursorrules, .github/copilot-instructions.md\n";
        return 0;
    }

    /**
     * @return string|null|false Null syncs every copy. False is an unknown provider file.
     */
    private function readProvider(string $root): string|null|false
    {
        $path = $root . '/.agents/provider';
        if (!is_file($path)) {
            return null;
        }

        $value = strtolower(trim((string) file_get_contents($path)));
        if ($value === '' || $value === 'all') {
            return null;
        }

        if (!in_array($value, $this->knownProviders, true)) {
            echo "Unknown provider '{$value}' in .agents/provider.\n";
            return false;
        }

        return $value;
    }
}
