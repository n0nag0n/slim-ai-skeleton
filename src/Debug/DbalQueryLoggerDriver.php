<?php

declare(strict_types=1);

namespace App\Debug;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

class DbalQueryLoggerDriver extends AbstractDriverMiddleware
{
    public function __construct(
        DriverInterface $driver,
        private readonly DbalQueries $queries,
    ) {
        parent::__construct($driver);
    }

    public function connect(array $params): \Doctrine\DBAL\Driver\Connection
    {
        return new DbalQueryLoggerConnection(parent::connect($params), $this->queries);
    }
}
