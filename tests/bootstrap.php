<?php

declare(strict_types=1);

$_ENV['APP_ENV'] = 'test';
$_ENV['DEBUG_MODE'] = 'false';
$_ENV['DB_DRIVER'] = 'pdo_sqlite';
$_ENV['DB_PATH'] = ':memory:';

require_once __DIR__ . '/../vendor/autoload.php';
