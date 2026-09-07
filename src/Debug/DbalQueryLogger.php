<?php

declare(strict_types=1);

namespace App\Debug;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

class DbalQueryLogger implements MiddlewareInterface
{
    private static ?DbalQueries $queries = null;

    public static function getQueries(): DbalQueries
    {
        return self::$queries ??= new DbalQueries();
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new DbalQueryLoggerDriver($driver, self::getQueries());
    }
}
