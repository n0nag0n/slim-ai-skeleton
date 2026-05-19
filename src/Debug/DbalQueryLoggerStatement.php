<?php

declare(strict_types=1);

namespace App\Debug;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

class DbalQueryLoggerStatement extends AbstractStatementMiddleware
{
    private array $params = [];
    private array $types = [];

    public function __construct(
        StatementInterface $statement,
        private readonly DbalQueries $queries,
        private readonly string $sql,
    ) {
        parent::__construct($statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->params[$param] = $value;
        $this->types[$param] = $type;
        parent::bindValue($param, $value, $type);
    }

    public function execute(): Result
    {
        $start = microtime(true);
        try {
            $result = parent::execute();
            $this->logQuery($start);
            $this->queries->setLastQueryMethod('prepare');
            $this->queries->setLastQueryRows($result->rowCount());
            return $result;
        } catch (\Throwable $e) {
            $this->logQuery($start);
            $this->queries->setLastQueryMethod('prepare');
            throw $e;
        }
    }

    private function logQuery(float $start): void
    {
        $this->queries->addQuery(
            microtime(true) - $start,
            $this->sql,
            $this->params,
            $this->types,
            $this->getSource(),
        );
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
