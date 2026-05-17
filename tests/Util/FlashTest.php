<?php

declare(strict_types=1);

namespace App\Test\Util;

use App\Util\ArraySession;
use App\Util\Flash;
use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $flash = new Flash(new ArraySession());
        $flash->set('success', 'Done.');
        $this->assertSame('Done.', $flash->get('success'));
    }

    public function testFlashDeletedAfterOneRead(): void
    {
        $flash = new Flash(new ArraySession());
        $flash->set('success', 'Done.');
        $this->assertSame('Done.', $flash->get('success'));
        $this->assertNull($flash->get('success'));
    }

    public function testHas(): void
    {
        $flash = new Flash(new ArraySession());
        $this->assertFalse($flash->has('error'));
        $flash->set('error', 'Something went wrong.');
        $this->assertTrue($flash->has('error'));
    }

    public function testGetReturnsDefault(): void
    {
        $flash = new Flash(new ArraySession());
        $this->assertNull($flash->get('nonexistent'));
        $this->assertSame('fallback', $flash->get('nonexistent', 'fallback'));
    }
}
