<?php

namespace App\Test\Util;

use App\Util\Flash;
use App\Util\Session;
use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testSetAndGetSameRequest(): void
    {
        $session = new Session;
        $flash = new Flash($session);
        $flash->set('success', 'Done.');
        $this->assertSame('Done.', $flash->get('success'));
    }

    public function testFlashDeletedAfterOneReadCrossRequest(): void
    {
        // Simulate Request A (POST) — set flash
        $sessionA = new Session;
        $flashA = new Flash($sessionA);
        $flashA->set('success', 'Operation completed.');
        $sessionA->save();

        // Simulate Request B (GET after redirect) — read flash
        $sessionB = new Session;
        $flashB = new Flash($sessionB);
        $this->assertTrue($flashB->has('success'), 'Flash should exist on first read');
        $this->assertSame('Operation completed.', $flashB->get('success'));
        $sessionB->save();

        // Simulate Request C (next GET) — flash should be GONE
        $sessionC = new Session;
        $flashC = new Flash($sessionC);
        $this->assertFalse($flashC->has('success'), 'Flash should be deleted after one read');
        $this->assertNull($flashC->get('success'));
    }
}
