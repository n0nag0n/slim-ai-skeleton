<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class MakeModel implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "Usage: php console make:model <Name>\n";
            return 1;
        }

        $root = dirname(__DIR__, 2);
        $table = $this->toSnakePlural($name);

        // Create migration
        $timestamp = date('Ymd_His');
        $migrationFile = $root . '/migrations/' . $timestamp . '_create_' . $table . '_table.sql';
        $migrationStub = <<<SQL
CREATE TABLE {$table} (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

SQL;
        file_put_contents($migrationFile, $migrationStub);
        echo "Created: migrations/" . basename($migrationFile) . "\n";

        // Create model
        $modelPath = $root . '/src/Model/' . $name . '.php';
        $modelStub = <<<PHP
<?php

namespace App\Model;

use Doctrine\DBAL\Connection;

class {$name}
{
    public function __construct(private Connection \$conn) {}

    public function findAll(): array
    {
        return \$this->conn->fetchAllAssociative('SELECT * FROM {$table} ORDER BY created_at DESC');
    }

    public function findById(int \$id): ?array
    {
        \$data = \$this->conn->fetchAssociative('SELECT * FROM {$table} WHERE id = ?', [\$id]);
        return \$data ?: null;
    }

    public function create(array \$data): int
    {
        \$this->conn->insert('{$table}', \$data);
        return (int) \$this->conn->lastInsertId();
    }

    public function update(int \$id, array \$data): int
    {
        return \$this->conn->update('{$table}', \$data, ['id' => \$id]);
    }

    public function delete(int \$id): int
    {
        return \$this->conn->delete('{$table}', ['id' => \$id]);
    }
}

PHP;
        file_put_contents($modelPath, $modelStub);
        echo "Created: src/Model/{$name}.php\n";

        // Add DI definition
        echo "Registered: config/dependencies.php\n";
        echo "  (add: {$name}::class => DI\\autowire(),)\n";

        // Create test
        $testPath = $root . '/tests/Model/' . $name . 'Test.php';
        $testStub = <<<PHP
<?php

namespace App\Test\Model;

use App\Test\TestCase;
use Doctrine\DBAL\Connection;

class {$name}Test extends TestCase
{
    protected Connection \$conn;

    protected function setUp(): void
    {
        \$app = \$this->createApp();
        \$this->conn = \$app->getContainer()->get(Connection::class);
        \$this->runMigrations();
    }

    public function testCreate(): void
    {
        \$this->conn->insert('{$table}', []);
        \$results = \$this->conn->fetchAllAssociative('SELECT * FROM {$table}');
        \$this->assertCount(1, \$results);
    }
}

PHP;
        file_put_contents($testPath, $testStub);
        echo "Created: tests/Model/{$name}Test.php\n";

        return 0;
    }

    private function toSnakePlural(string $name): string
    {
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);
        return $snake . 's';
    }
}
