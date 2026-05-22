<?php

declare(strict_types=1);

namespace App\Test\Util;

use App\Util\MigrationFileResolver;
use PHPUnit\Framework\TestCase;

class MigrationFileResolverTest extends TestCase
{
    private string $tempDir;
    private string $origDriver;

    protected function setUp(): void
    {
        $this->origDriver = $_ENV['DB_DRIVER'] ?? 'pdo_sqlite';
        $this->tempDir = sys_get_temp_dir() . '/slim_migration_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $_ENV['DB_DRIVER'] = $this->origDriver;
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }

    public function testReturnsBaseFileWhenNoOverrideExists(): void
    {
        $baseFile = $this->tempDir . '/001_create_posts.sql';
        file_put_contents($baseFile, 'CREATE TABLE posts (id INTEGER);');

        $_ENV['DB_DRIVER'] = 'pdo_mysql';
        $result = MigrationFileResolver::resolve($baseFile);

        $this->assertSame($baseFile, $result);
    }

    public function testResolvesToMysqlOverrideWhenDriverIsMysql(): void
    {
        $baseFile = $this->tempDir . '/001_create_posts.sql';
        $overrideFile = $this->tempDir . '/001_create_posts.mysql.sql';
        file_put_contents($baseFile, 'CREATE TABLE posts (id INTEGER);');
        file_put_contents($overrideFile, 'CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY);');

        $_ENV['DB_DRIVER'] = 'pdo_mysql';
        $result = MigrationFileResolver::resolve($baseFile);

        $this->assertSame($overrideFile, $result);
    }

    public function testResolvesToMysqlOverrideForPdoMariadb(): void
    {
        $baseFile = $this->tempDir . '/001_create_posts.sql';
        $overrideFile = $this->tempDir . '/001_create_posts.mysql.sql';
        file_put_contents($baseFile, 'CREATE TABLE posts (id INTEGER);');
        file_put_contents($overrideFile, 'CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY);');

        $_ENV['DB_DRIVER'] = 'pdo_mariadb';
        $result = MigrationFileResolver::resolve($baseFile);

        $this->assertSame($overrideFile, $result);
    }

    public function testReturnsBaseFileForSqlite(): void
    {
        $baseFile = $this->tempDir . '/001_create_posts.sql';
        $overrideFile = $this->tempDir . '/001_create_posts.mysql.sql';
        file_put_contents($baseFile, 'CREATE TABLE posts (id INTEGER);');
        file_put_contents($overrideFile, 'CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY);');

        $_ENV['DB_DRIVER'] = 'pdo_sqlite';
        $result = MigrationFileResolver::resolve($baseFile);

        $this->assertSame($baseFile, $result);
    }

    public function testResolvesToPostgresOverrideWhenDriverIsPostgres(): void
    {
        $baseFile = $this->tempDir . '/001_create_posts.sql';
        $overrideFile = $this->tempDir . '/001_create_posts.pgsql.sql';
        file_put_contents($baseFile, 'CREATE TABLE posts (id INTEGER);');
        file_put_contents($overrideFile, 'CREATE TABLE posts (id SERIAL PRIMARY KEY);');

        $_ENV['DB_DRIVER'] = 'pdo_pgsql';
        $result = MigrationFileResolver::resolve($baseFile);

        $this->assertSame($overrideFile, $result);
    }
}
