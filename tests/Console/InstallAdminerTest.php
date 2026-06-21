<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\InstallAdminer;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class InstallAdminerTest extends TestCase
{
    private string $tempDir;
    private string $dest;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/slim_test_adminer_' . uniqid();
        mkdir($this->tempDir . '/public', 0755, true);
        $this->dest = $this->tempDir . '/public/adminer.php';
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testShowsUsageWhenDriverMissing(): void
    {
        $command = new InstallAdminer($this->tempDir);

        ob_start();
        $exitCode = $command->execute([], (new ContainerBuilder())->build());
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Usage: php console adminer:install', $output);
    }

    public function testShowsUsageWhenDriverInvalid(): void
    {
        $command = new InstallAdminer($this->tempDir);

        ob_start();
        $exitCode = $command->execute(['postgres'], (new ContainerBuilder())->build());
        $output = ob_get_clean();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Usage: php console adminer:install', $output);
    }

    public function testInstallsMysqlAdminer(): void
    {
        $fetch = function (string $url): string {
            $this->assertSame('https://adminer.org/latest-mysql.php', $url);
            return '<?php // mysql adminer';
        };

        $command = new InstallAdminer($this->tempDir, $fetch);

        ob_start();
        $exitCode = $command->execute(['mysql'], (new ContainerBuilder())->build());
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->dest);
        $this->assertSame('<?php // mysql adminer', file_get_contents($this->dest));
        $this->assertStringContainsString('Installed Adminer (mysql)', $output);
    }

    public function testInstallsSqliteAdminerFromLatestRelease(): void
    {
        $releaseJson = json_encode([
            'assets' => [
                [
                    'name' => 'adminer-5.4.2-sqlite-fr.php',
                    'browser_download_url' => 'https://example.com/fr.php',
                ],
                [
                    'name' => 'adminer-5.4.2-sqlite-en.php',
                    'browser_download_url' => 'https://example.com/en.php',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $fetch = function (string $url) use ($releaseJson): string {
            $api = 'https://api.github.com/repos/'
                . 'FrancoisCapon/LoginToASqlite3DatabaseWithoutCredentialsWithAdminer/releases/latest';
            if ($url === $api) {
                return $releaseJson;
            }

            $this->assertSame('https://example.com/en.php', $url);
            return '<?php // sqlite adminer';
        };

        $command = new InstallAdminer($this->tempDir, $fetch);

        ob_start();
        $exitCode = $command->execute(['sqlite'], (new ContainerBuilder())->build());
        $output = ob_get_clean();

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->dest);
        $this->assertSame('<?php // sqlite adminer', file_get_contents($this->dest));
        $this->assertStringContainsString('Installed Adminer (sqlite)', $output);
        $this->assertStringContainsString('https://example.com/en.php', $output);
    }

    public function testSqliteFailsWhenEnglishAssetMissing(): void
    {
        $releaseJson = json_encode([
            'assets' => [
                [
                    'name' => 'adminer-5.4.2-sqlite-fr.php',
                    'browser_download_url' => 'https://example.com/fr.php',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $fetch = fn (string $url): string => $releaseJson;

        $command = new InstallAdminer($this->tempDir, $fetch);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No English SQLite Adminer asset found');

        $command->execute(['sqlite'], (new ContainerBuilder())->build());
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($path);
    }
}
