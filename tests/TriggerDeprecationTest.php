<?php

declare(strict_types=1);

namespace App\Test;

use PHPUnit\Framework\TestCase;

class TriggerDeprecationTest extends TestCase
{
    public function testEmitsASilencedDeprecation(): void
    {
        $captured = null;
        set_error_handler(static function (int $severity, string $message) use (&$captured): bool {
            $captured = [$severity, $message];
            return true;
        });

        try {
            trigger_deprecation('example/package', '1.2', 'Hello %s', 'world');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([E_USER_DEPRECATED, 'Since example/package 1.2: Hello world'], $captured);
    }
}
