<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class SyncAiInstructions implements CommandInterface
{
    /** @var array<int, string> */
    private array $targets = [
        'CLAUDE.md',
        '.github/copilot-instructions.md',
        'GEMINI.md',
        '.cursorrules',
        '.windsurfrules',
        '.continue/rules/instructions.md',
        'cline_docs/CONTEXT.md',
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
        $count = 0;

        foreach ($this->targets as $target) {
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
        return 0;
    }
}
