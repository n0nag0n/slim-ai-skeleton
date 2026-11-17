<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class SelectAiProvider implements CommandInterface
{
    /**
     * keep: instruction files to leave in place besides AGENTS.md.
     * linkSkills: directory that should symlink each skill. Null when the
     * tool already scans .agents/skills. Cursor does.
     *
     * @var array<string, array{keep: array<int, string>, linkSkills: ?string}>
     */
    private array $providers = [
        'grok' => ['keep' => [], 'linkSkills' => null],
        'codex' => ['keep' => [], 'linkSkills' => null],
        'claude' => ['keep' => ['CLAUDE.md'], 'linkSkills' => '.claude/skills'],
        'cursor' => ['keep' => ['.cursorrules'], 'linkSkills' => null],
        'copilot' => ['keep' => ['.github/copilot-instructions.md'], 'linkSkills' => null],
        'gemini' => ['keep' => ['GEMINI.md'], 'linkSkills' => null],
        'windsurf' => ['keep' => ['.windsurfrules'], 'linkSkills' => null],
        'cline' => ['keep' => ['cline_docs/CONTEXT.md'], 'linkSkills' => null],
    ];

    /** @var array<int, string> */
    private array $instructionFiles = [
        'CLAUDE.md',
        '.github/copilot-instructions.md',
        'GEMINI.md',
        '.cursorrules',
        '.windsurfrules',
        'cline_docs/CONTEXT.md',
    ];

    /**
     * Skill link directories this command has created. Switching providers
     * removes the ones that do not belong to the new choice.
     *
     * @var array<int, string>
     */
    private array $skillLinkDirs = [
        '.claude/skills',
        '.cursor/skills',
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
        $choice = isset($args[0]) ? strtolower($args[0]) : null;

        if ($choice === null && (getenv('COMPOSER_NO_INTERACTION') === '1' || !stream_isatty(STDIN))) {
            echo "No interactive terminal. Kept every provider file.\n";
            echo "Choose one later with: php console ai:provider <name>\n";
            return 0;
        }

        if ($choice === null) {
            $choice = $this->prompt();
        }

        if ($choice === 'all') {
            $this->writeProvider($root, 'all');
            echo "Kept every provider file.\n";
            return 0;
        }

        if (!isset($this->providers[$choice])) {
            echo "Unknown provider '{$choice}'.\n";
            echo 'Use one of: ' . implode(', ', array_keys($this->providers)) . ", all\n";
            return 1;
        }

        $this->apply($root, $choice);
        return 0;
    }

    private function prompt(): string
    {
        echo "Which AI tool should this project keep instructions for?\n";
        foreach (array_keys($this->providers) as $name) {
            echo "  {$name}\n";
        }
        echo "  all\n";
        echo "Provider: ";

        $answer = strtolower(trim((string) fgets(STDIN)));
        return $answer;
    }

    private function apply(string $root, string $choice): void
    {
        $provider = $this->providers[$choice];

        foreach ($this->instructionFiles as $relative) {
            if (in_array($relative, $provider['keep'], true)) {
                continue;
            }
            $this->deleteFile($root, $relative);
            echo "Removed: {$relative}\n";
        }

        $this->removeOtherSkillLinks($root, $provider['linkSkills']);

        if ($provider['linkSkills'] !== null) {
            $this->linkSkills($root, $provider['linkSkills']);
        }

        $this->writeProvider($root, $choice);
        echo "\nKept AGENTS.md and .agents/skills/ for {$choice}.\n";
    }

    private function writeProvider(string $root, string $choice): void
    {
        $dir = $root . '/.agents';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/provider', $choice . "\n");
    }

    private function deleteFile(string $root, string $relative): void
    {
        $path = $root . '/' . $relative;
        if (is_file($path)) {
            unlink($path);
        }

        $dir = dirname($path);
        while ($dir !== $root && $this->isEmptyDir($dir)) {
            rmdir($dir);
            $dir = dirname($dir);
        }
    }

    private function isEmptyDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $items = scandir($dir);
        return $items === false || count($items) === 2;
    }

    private function removeOtherSkillLinks(string $root, ?string $keep): void
    {
        foreach ($this->skillLinkDirs as $relative) {
            if ($keep === $relative) {
                continue;
            }
            $this->removeSkillLinks($root, $relative);
        }
    }

    private function removeSkillLinks(string $root, string $relativeDir): void
    {
        $dest = $root . '/' . $relativeDir;
        if (!is_dir($dest)) {
            return;
        }

        $items = scandir($dest);
        if ($items === false) {
            return;
        }

        foreach ($items as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $dest . '/' . $name;
            if (!is_link($path)) {
                continue;
            }
            unlink($path);
            echo "Removed skill link: {$relativeDir}/{$name}\n";
        }

        $dir = $dest;
        while ($dir !== $root && $this->isEmptyDir($dir)) {
            rmdir($dir);
            $dir = dirname($dir);
        }
    }

    private function linkSkills(string $root, string $relativeDir): void
    {
        $source = $root . '/.agents/skills';
        $dest = $root . '/' . $relativeDir;
        if (!is_dir($source)) {
            echo "No skills directory at .agents/skills\n";
            return;
        }
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $skills = scandir($source);
        if ($skills === false) {
            return;
        }

        foreach ($skills as $name) {
            if ($name === '.' || $name === '..' || !is_dir($source . '/' . $name)) {
                continue;
            }
            if (!is_file($source . '/' . $name . '/SKILL.md')) {
                continue;
            }
            $link = $dest . '/' . $name;
            if (is_link($link) || file_exists($link)) {
                echo "Skill link already exists: {$relativeDir}/{$name}\n";
                continue;
            }
            symlink('../../.agents/skills/' . $name, $link);
            echo "Linked: {$relativeDir}/{$name}\n";
        }
    }
}
