<?php

namespace App\Test\Util;

use App\Util\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testPassesWithNoRules(): void
    {
        $v = new Validator(['name' => 'test']);
        $this->assertTrue($v->passes());
        $this->assertFalse($v->fails());
    }

    public function testRequiredPasses(): void
    {
        $v = new Validator(['name' => 'test']);
        $v->required('name');
        $this->assertTrue($v->passes());
    }

    public function testRequiredFailsOnEmpty(): void
    {
        $v = new Validator(['name' => ''], ['name' => 'Name']);
        $v->required('name');
        $this->assertTrue($v->fails());
        $this->assertSame(['Name is required.'], $v->getErrors()['name']);
    }

    public function testRequiredFailsOnMissing(): void
    {
        $v = new Validator([], ['name' => 'Name']);
        $v->required('name');
        $this->assertTrue($v->fails());
    }

    public function testRequiredFailsOnEmptyArray(): void
    {
        $v = new Validator(['tags' => []]);
        $v->required('tags');
        $this->assertTrue($v->fails());
    }

    public function testRequiredMultipleFields(): void
    {
        $v = new Validator(['a' => '', 'b' => 'val'], ['a' => 'A', 'b' => 'B']);
        $v->required('a', 'b');
        $this->assertCount(1, $v->getErrors()['a']);
        $this->assertArrayNotHasKey('b', $v->getErrors());
    }

    public function testEmailPasses(): void
    {
        $v = new Validator(['email' => 'user@example.com']);
        $v->email('email');
        $this->assertTrue($v->passes());
    }

    public function testEmailFails(): void
    {
        $v = new Validator(['email' => 'not-an-email'], ['email' => 'Email']);
        $v->email('email');
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('Email', $v->getFirstError());
    }

    public function testEmailSkipsOnEmpty(): void
    {
        $v = new Validator(['email' => '']);
        $v->email('email');
        $this->assertTrue($v->passes());
    }

    public function testMinLengthPasses(): void
    {
        $v = new Validator(['pass' => '12345678']);
        $v->minLength('pass', 8);
        $this->assertTrue($v->passes());
    }

    public function testMinLengthFails(): void
    {
        $v = new Validator(['pass' => '123'], ['pass' => 'Password']);
        $v->minLength('pass', 8);
        $this->assertStringContainsString('Password', $v->getFirstError());
    }

    public function testMinLengthSkipsOnEmpty(): void
    {
        $v = new Validator(['pass' => '']);
        $v->minLength('pass', 8);
        $this->assertTrue($v->passes());
    }

    public function testMaxLengthPasses(): void
    {
        $v = new Validator(['name' => 'abc']);
        $v->maxLength('name', 3);
        $this->assertTrue($v->passes());
    }

    public function testMaxLengthFails(): void
    {
        $v = new Validator(['name' => 'abcd'], ['name' => 'Name']);
        $v->maxLength('name', 3);
        $this->assertTrue($v->fails());
    }

    public function testMatchesPasses(): void
    {
        $v = new Validator(['pw' => 'secret', 'pw2' => 'secret']);
        $v->matches('pw', 'pw2');
        $this->assertTrue($v->passes());
    }

    public function testMatchesFails(): void
    {
        $v = new Validator(['pw' => 'secret', 'pw2' => 'different'], ['pw' => 'Password']);
        $v->matches('pw', 'pw2');
        $this->assertTrue($v->fails());
    }

    public function testMatchesSkipsOnEmpty(): void
    {
        $v = new Validator(['pw' => '', 'pw2' => '']);
        $v->matches('pw', 'pw2');
        $this->assertTrue($v->passes());
    }

    public function testNumericPasses(): void
    {
        $v = new Validator(['age' => '42']);
        $v->numeric('age');
        $this->assertTrue($v->passes());
    }

    public function testNumericFails(): void
    {
        $v = new Validator(['age' => 'abc'], ['age' => 'Age']);
        $v->numeric('age');
        $this->assertTrue($v->fails());
    }

    public function testNumericSkipsOnEmpty(): void
    {
        $v = new Validator(['age' => '']);
        $v->numeric('age');
        $this->assertTrue($v->passes());
    }

    public function testInArrayPasses(): void
    {
        $v = new Validator(['role' => 'admin']);
        $v->inArray('role', ['admin', 'user']);
        $this->assertTrue($v->passes());
    }

    public function testInArrayFails(): void
    {
        $v = new Validator(['role' => 'root'], ['role' => 'Role']);
        $v->inArray('role', ['admin', 'user']);
        $this->assertTrue($v->fails());
    }

    public function testInArraySkipsOnEmpty(): void
    {
        $v = new Validator(['role' => '']);
        $v->inArray('role', ['admin', 'user']);
        $this->assertTrue($v->passes());
    }

    public function testUrlPasses(): void
    {
        $v = new Validator(['site' => 'https://example.com']);
        $v->url('site');
        $this->assertTrue($v->passes());
    }

    public function testUrlFails(): void
    {
        $v = new Validator(['site' => 'not-a-url'], ['site' => 'Site']);
        $v->url('site');
        $this->assertTrue($v->fails());
    }

    public function testUrlSkipsOnEmpty(): void
    {
        $v = new Validator(['site' => '']);
        $v->url('site');
        $this->assertTrue($v->passes());
    }

    public function testDefaultLabelUsesFieldName(): void
    {
        $v = new Validator(['first_name' => '']);
        $v->required('first_name');
        $this->assertStringContainsString('First name', $v->getFirstError());
    }

    public function testGetFirstErrorReturnsNull(): void
    {
        $v = new Validator(['name' => 'test']);
        $this->assertNull($v->getFirstError());
    }

    public function testChaining(): void
    {
        $v = new Validator([
            'name' => '',
            'email' => 'bad',
            'age' => 'not-num',
        ], [
            'name' => 'Name',
            'email' => 'Email',
            'age' => 'Age',
        ]);

        $v->required('name')
          ->email('email')
          ->minLength('name', 3)
          ->numeric('age');

        $this->assertCount(3, $v->getErrors());
    }

    public function testPassesWithAllValid(): void
    {
        $v = new Validator([
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => '25',
            'role' => 'admin',
            'site' => 'https://example.com',
        ]);

        $v->required('name', 'email')
          ->email('email')
          ->minLength('name', 2)
          ->maxLength('name', 100)
          ->numeric('age')
          ->inArray('role', ['admin', 'user'])
          ->url('site');

        $this->assertTrue($v->passes());
    }
}
