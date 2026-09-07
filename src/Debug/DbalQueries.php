<?php

declare(strict_types=1);

namespace App\Debug;

final class DbalQueries
{
    private array $queries = [];

    public function addQuery(
        float $duration,
        string $sql,
        ?array $params = null,
        ?array $types = null,
        ?string $source = null,
        int $rows = 0,
    ): void {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params ?? [],
            'types' => $types ?? [],
            'time' => round($duration, 4),
            'rows' => $rows,
            'source' => $source ?? 'unknown',
        ];
    }

    public static function caller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            $line = $frame['line'] ?? 0;
            if (!str_contains($file, 'vendor/') && !str_contains($file, 'src/Debug/')) {
                return $file . ':' . $line;
            }
        }
        return 'unknown';
    }

    public function getAll(): array
    {
        return $this->queries;
    }
}
