<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use Doctrine\DBAL\Connection;

interface SeederInterface
{
    public function run(Connection $conn): void;
}