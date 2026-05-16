<?php

declare(strict_types=1);

namespace App\Debug;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

class DbalQueryLogger implements MiddlewareInterface
{
    private static ?DbalQueries $queries = null;

    public function __construct(
        private readonly ?DbalQueries $queryCollection = null,
    ) {
        self::$queries ??= $this->queryCollection ?? new DbalQueries();
    }

    public static function getQueries(): DbalQueries
    {
        return self::$queries ??= new DbalQueries();
    }

    public function getQueryCollection(): DbalQueries
    {
        return $this->queryCollection ?? self::getQueries();
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new DbalQueryLoggerDriver($driver, self::getQueries());
    }
}
