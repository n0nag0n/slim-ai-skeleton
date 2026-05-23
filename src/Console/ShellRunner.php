<?php

declare(strict_types=1);

namespace App\Console;

class ShellRunner
{
    /** @return array{exit_code: int} */
    public function run(string $command): array
    {
        $output = [];
        $exitCode = 0;
        passthru($command, $exitCode);
        return ['exit_code' => $exitCode];
    }

    /** @return array{output: list<string>, exit_code: int} */
    public function capture(string $command): array
    {
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        return ['output' => $output, 'exit_code' => $exitCode];
    }
}
