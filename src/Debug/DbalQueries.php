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
    ): void {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params ?? [],
            'types' => $types ?? [],
            'time' => round($duration, 4),
            'rows' => 0,
            'source' => $source ?? 'unknown',
            'method' => 'unknown',
        ];
    }

    public function setLastQueryRows(int $rows): void
    {
        if (!empty($this->queries)) {
            $this->queries[array_key_last($this->queries)]['rows'] = $rows;
        }
    }

    public function setLastQueryMethod(string $method): void
    {
        if (!empty($this->queries)) {
            $this->queries[array_key_last($this->queries)]['method'] = $method;
        }
    }

    public function getAll(): array
    {
        return $this->queries;
    }

    public function count(): int
    {
        return count($this->queries);
    }
}
