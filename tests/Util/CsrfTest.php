<?php

declare(strict_types=1);

namespace App\Test\Util;

use App\Util\ArraySession;
use App\Util\Csrf;
use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    private Csrf $csrf;

    protected function setUp(): void
    {
        $this->csrf = new Csrf(new ArraySession());
    }

    public function testGenerateReturnsToken(): void
    {
        $token = $this->csrf->generate();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token));
    }

    public function testGenerateIsIdempotent(): void
    {
        $first = $this->csrf->generate();
        $second = $this->csrf->generate();
        $this->assertSame($first, $second);
    }

    public function testValidateValidToken(): void
    {
        $token = $this->csrf->generate();
        $this->assertTrue($this->csrf->validate($token));
    }

    public function testValidateInvalidToken(): void
    {
        $this->csrf->generate();
        $this->assertFalse($this->csrf->validate('invalid-token'));
    }

    public function testValidateWithNoToken(): void
    {
        $this->assertFalse($this->csrf->validate('anything'));
    }

    public function testRegenerateChangesToken(): void
    {
        $old = $this->csrf->generate();
        $this->csrf->regenerate();
        $new = $this->csrf->generate();
        $this->assertNotSame($old, $new);
    }

    public function testRegenerateInvalidatesOldToken(): void
    {
        $old = $this->csrf->generate();
        $this->csrf->regenerate();
        $this->assertFalse($this->csrf->validate($old));
    }
}
