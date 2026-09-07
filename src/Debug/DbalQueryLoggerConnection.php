<?php

declare(strict_types=1);

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
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, DbalQueries::caller(), $result->rowCount());
            return $result;
        } catch (\Throwable $e) {
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, DbalQueries::caller());
            throw $e;
        }
    }

    public function exec(string $sql): int|string
    {
        $start = microtime(true);
        try {
            $result = parent::exec($sql);
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, DbalQueries::caller(), (int) $result);
            return $result;
        } catch (\Throwable $e) {
            $this->queries->addQuery(microtime(true) - $start, $sql, null, null, DbalQueries::caller());
            throw $e;
        }
    }
}
