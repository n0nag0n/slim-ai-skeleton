<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class InstallAdminer implements CommandInterface
{
    private const MYSQL_URL = 'https://adminer.org/latest-mysql.php';

    private const SQLITE_RELEASES_API = 'https://api.github.com/repos/'
        . 'FrancoisCapon/LoginToASqlite3DatabaseWithoutCredentialsWithAdminer/releases/latest';

    /** @var \Closure(string): string */
    private \Closure $fetch;

    public function __construct(
        private ?string $projectRoot = null,
        ?\Closure $fetch = null,
    ) {
        $this->fetch = $fetch ?? fn (string $url): string => $this->download($url);
    }

    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $driver = $args[0] ?? '';

        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            echo "Usage: php console adminer:install <mysql|sqlite>\n";
            echo "  mysql   Download Adminer for MySQL/MariaDB from adminer.org\n";
            echo "  sqlite  Download modified SQLite Adminer from GitHub releases\n";
            return 1;
        }

        $root = $this->projectRoot ?? dirname(__DIR__, 2);
        $dest = $root . '/public/adminer.php';

        $url = $driver === 'mysql'
            ? self::MYSQL_URL
            : $this->resolveSqliteDownloadUrl();

        $content = ($this->fetch)($url);

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        if (file_put_contents($dest, $content) === false) {
            echo "Failed to write: {$dest}\n";
            return 1;
        }

        echo "Installed Adminer ({$driver}) to public/adminer.php\n";
        echo "Source: {$url}\n";

        return 0;
    }

    private function resolveSqliteDownloadUrl(): string
    {
        $json = ($this->fetch)(self::SQLITE_RELEASES_API);

        /** @var array{assets?: list<array{name: string, browser_download_url: string}>} $release */
        $release = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        foreach ($release['assets'] ?? [] as $asset) {
            if (str_ends_with($asset['name'], '-sqlite-en.php')) {
                return $asset['browser_download_url'];
            }
        }

        throw new \RuntimeException('No English SQLite Adminer asset found in latest GitHub release');
    }

    private function download(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: slim-ai-skeleton-adminer-installer\r\n",
                'timeout' => 30,
            ],
        ]);

        $content = file_get_contents($url, false, $context);

        if ($content === false) {
            throw new \RuntimeException("Failed to download: {$url}");
        }

        return $content;
    }
}
