<?php

declare(strict_types=1);

namespace App\Test\Util;

use App\Util\EnvFile;
use PHPUnit\Framework\TestCase;

class EnvFileTest extends TestCase
{
    private string $directory;

    /** @var list<string> */
    private array $names = [];

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/env-file-' . uniqid('', true);
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach ($this->names as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
        }

        $path = $this->directory . '/.env';
        if (is_file($path)) {
            unlink($path);
        }
        rmdir($this->directory);
    }

    public function testMissingFileLeavesTheEnvironmentAlone(): void
    {
        EnvFile::load($this->directory);

        $this->assertArrayNotHasKey('ENV_FILE_MISSING', $_ENV);
    }

    public function testLoadsCommentsQuotesAndBlankValues(): void
    {
        $this->write(<<<'ENV'
            # comment
            PLAIN=dev
            EMPTY=
            QUOTED="hello world"
            SINGLE='keep $literal'
            ESCAPED="line\nbreak"
            COMMENTED=value # note
            HASH=# not a value

            ENV);

        EnvFile::load($this->directory);

        $this->assertSame('dev', $_ENV['PLAIN']);
        $this->assertSame('', $_ENV['EMPTY']);
        $this->assertSame('hello world', $_ENV['QUOTED']);
        $this->assertSame('keep $literal', $_ENV['SINGLE']);
        $this->assertSame("line\nbreak", $_ENV['ESCAPED']);
        $this->assertSame('value', $_ENV['COMMENTED']);
        $this->assertSame('', $_ENV['HASH']);
        $this->assertSame($_ENV['QUOTED'], $_SERVER['QUOTED']);
    }

    public function testDoesNotOverrideExistingVariables(): void
    {
        $_ENV['KEEP_ME'] = 'from-server';
        $this->names[] = 'KEEP_ME';
        $this->write("KEEP_ME=from-file\nOTHER=kept\n");

        EnvFile::load($this->directory);

        $this->assertSame('from-server', $_ENV['KEEP_ME']);
        $this->assertSame('kept', $_ENV['OTHER']);
    }

    public function testRejectsVariableExpansion(): void
    {
        $this->write("NAME=\"hello \$USER\"\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Variable expansion is not supported');

        EnvFile::load($this->directory);
    }

    public function testRejectsTheExportKeyword(): void
    {
        $this->write("export APP_ENV=dev\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('export keyword');

        EnvFile::load($this->directory);
    }

    public function testRejectsAnUnclosedQuote(): void
    {
        $this->write("NAME=\"hello\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unclosed quote');

        EnvFile::load($this->directory);
    }

    private function write(string $contents): void
    {
        file_put_contents($this->directory . '/.env', $contents);
        preg_match_all('/^([A-Za-z_.][A-Za-z0-9_.]*)=/m', $contents, $matches);
        foreach ($matches[1] as $name) {
            $this->names[] = $name;
        }
    }
}
