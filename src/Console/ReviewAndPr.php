<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class ReviewAndPr implements CommandInterface
{
    /** @var array<int, array{command: string, label: string}> */
    private array $checks = [
        ['command' => 'composer lint', 'label' => 'Lint'],
        ['command' => 'composer stan', 'label' => 'Static Analysis'],
        ['command' => 'composer test', 'label' => 'Tests'],
    ];

    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $branch = $args[0] ?? null;
        $message = $args[1] ?? null;

        echo "Running automated pre-flight checks...\n\n";

        $allPassed = true;
        foreach ($this->checks as $check) {
            echo "[{$check['label']}] Running...\n";
            passthru($check['command'], $exitCode);
            if ($exitCode !== 0) {
                $allPassed = false;
                echo "[{$check['label']}] FAILED (exit {$exitCode})\n\n";
                break;
            }
            echo "[{$check['label']}] PASSED\n\n";
        }

        if (!$allPassed) {
            echo "Checks did not pass. Fix the issues before creating a PR.\n";
            return 1;
        }

        echo "All checks passed.\n\n";

        // Verify uncommitted changes exist
        $statusOutput = [];
        $statusCode = 0;
        exec('git status --porcelain', $statusOutput, $statusCode);
        if ($statusCode !== 0 || count($statusOutput) === 0) {
            echo "No uncommitted changes to commit.\n";
            return 1;
        }

        // Prompt for branch name if not provided
        if (!$branch) {
            echo "Branch name: ";
            $branch = trim(fgets(\STDIN) ?: '');
            if (!$branch) {
                echo "Branch name is required.\n";
                return 1;
            }
        }

        // Prompt for commit message if not provided
        if (!$message) {
            echo "Commit message: ";
            $message = trim(fgets(\STDIN) ?: '');
            if (!$message) {
                echo "Commit message is required.\n";
                return 1;
            }
        }

        // Create branch
        passthru("git checkout -b " . escapeshellarg($branch), $exitCode);
        if ($exitCode !== 0) {
            echo "Failed to create branch. It may already exist.\n";
            return 1;
        }

        // Stage everything
        passthru('git add -A', $exitCode);
        if ($exitCode !== 0) {
            echo "Failed to stage changes.\n";
            return 1;
        }

        // Commit
        passthru('git commit -m ' . escapeshellarg($message), $exitCode);
        if ($exitCode !== 0) {
            echo "Failed to commit.\n";
            return 1;
        }

        // Push
        passthru('git push -u origin ' . escapeshellarg($branch), $exitCode);
        if ($exitCode !== 0) {
            echo "Failed to push branch.\n";
            return 1;
        }

        // Create PR
        passthru('gh pr create --fill', $exitCode);
        if ($exitCode !== 0) {
            echo "Failed to create PR via gh CLI.\n";
            echo "Run manually: gh pr create --base main --head " . escapeshellarg($branch) . "\n";
            return 1;
        }

        echo "\nPR created successfully.\n";
        return 0;
    }
}
