<?php

namespace App\Debug;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;

class DbalQueryLoggerConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly DbalQueries $queries,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): \Doctrine\DBAL\Driver\Statement
    {
        return new DbalQueryLoggerStatement(parent::prepare($sql), $this->queries, $sql);
    }

    public function query(string $sql): Result
    {
        $start = microtime(true);
        try {
            $result = parent::query($sql);
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, $this->getSource());
            $this->queries->setLastQueryMethod('query');
            $this->queries->setLastQueryRows($result->rowCount());
            return $result;
        } catch (\Throwable $e) {
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, $this->getSource());
            $this->queries->setLastQueryMethod('query');
            throw $e;
        }
    }

    public function exec(string $sql): int|string
    {
        $start = microtime(true);
        try {
            $result = parent::exec($sql);
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, $this->getSource());
            $this->queries->setLastQueryMethod('exec');
            $this->queries->setLastQueryRows((int) $result);
            return $result;
        } catch (\Throwable $e) {
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, $this->getSource());
            $this->queries->setLastQueryMethod('exec');
            throw $e;
        }
    }

    private function getSource(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            $line = $frame['line'] ?? 0;
            if (!str_contains($file, 'vendor/') && !str_contains($file, 'src/Debug/')) {
                return $file . ':' . $line;
            }
        }
        return 'unknown';
    }
}
