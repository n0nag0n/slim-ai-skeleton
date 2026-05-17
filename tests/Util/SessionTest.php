<?php

declare(strict_types=1);

namespace App\Test\Util;

use App\Util\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        @session_start();
        $_SESSION = [];
        @session_destroy();
        session_id('');
        $this->session = new Session();
    }

    protected function tearDown(): void
    {
        @session_start();
        $_SESSION = [];
        @session_destroy();
        session_id('');
    }

    public function testStartReleasesLock(): void
    {
        $this->session->start();
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testSetAndGet(): void
    {
        $this->session->set('key', 'value');
        $this->assertSame('value', $this->session->get('key'));
    }

    public function testGetReturnsDefault(): void
    {
        $this->assertNull($this->session->get('nonexistent'));
        $this->assertSame('fallback', $this->session->get('nonexistent', 'fallback'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->session->has('key'));
        $this->session->set('key', 'value');
        $this->assertTrue($this->session->has('key'));
    }

    public function testDelete(): void
    {
        $this->session->set('key', 'value');
        $this->session->delete('key');
        $this->assertFalse($this->session->has('key'));
    }

    public function testClear(): void
    {
        $this->session->set('a', 1);
        $this->session->set('b', 2);
        $this->session->clear();
        $this->assertSame([], $this->session->all());
    }

    public function testAll(): void
    {
        $this->session->set('name', 'test');
        $this->session->set('role', 'admin');
        $all = $this->session->all();
        $this->assertSame('test', $all['name']);
        $this->assertSame('admin', $all['role']);
    }

    public function testGetId(): void
    {
        $id = $this->session->getId();
        $this->assertNotEmpty($id);
    }

    public function testSavePersistsToGlobalSession(): void
    {
        $this->session->set('persist_key', 'persist_value');
        $this->session->save();

        $this->assertArrayHasKey('persist_key', $_SESSION);
        $this->assertSame('persist_value', $_SESSION['persist_key']);
    }

    public function testDestroy(): void
    {
        $this->session->set('key', 'value');
        $this->session->destroy();
        $this->assertSame([], $this->session->all());
    }

    public function testMultipleSessionsAreIndependent(): void
    {
        $s1 = new Session();
        $s2 = new Session();

        $s1->set('foo', 'bar');
        $this->assertFalse($s2->has('foo'));
    }

    public function testRegenerateChangesId(): void
    {
        $oldId = $this->session->getId();
        $this->session->regenerate();
        $this->assertNotSame($oldId, $this->session->getId());
    }

    public function testIsIdempotent(): void
    {
        $this->session->start();
        $this->session->start();
        $this->session->set('x', 1);
        $this->session->save();
        $this->session->save();
        $this->assertSame(1, $this->session->get('x'));
    }
}
