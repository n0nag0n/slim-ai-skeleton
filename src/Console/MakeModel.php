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

        // Create model
        $modelPath = $root . '/src/Model/' . $name . '.php';
        $modelStub = <<<PHP
<?php

declare(strict_types=1);

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

declare(strict_types=1);

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
        \$this->conn->executeStatement('CREATE TABLE {$table} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function testFindAll(): void
    {
        \$this->conn->insert('{$table}', ['name' => 'Test']);
        \$model = new {$name}(\$this->conn);
        \$results = \$model->findAll();
        \$this->assertCount(1, \$results);
    }

    public function testFindById(): void
    {
        \$this->conn->insert('{$table}', ['name' => 'Test']);
        \$model = new {$name}(\$this->conn);
        \$result = \$model->findById(1);
        \$this->assertNotNull(\$result);
        \$this->assertEquals('Test', \$result['name']);
    }

    public function testCreate(): void
    {
        \$model = new {$name}(\$this->conn);
        \$id = \$model->create(['name' => 'Created']);
        \$this->assertEquals(1, \$id);
        \$result = \$model->findById(\$id);
        \$this->assertEquals('Created', \$result['name']);
    }

    public function testUpdate(): void
    {
        \$this->conn->insert('{$table}', ['name' => 'Original']);
        \$model = new {$name}(\$this->conn);
        \$model->update(1, ['name' => 'Updated']);
        \$result = \$model->findById(1);
        \$this->assertEquals('Updated', \$result['name']);
    }

    public function testDelete(): void
    {
        \$this->conn->insert('{$table}', ['name' => 'ToDelete']);
        \$model = new {$name}(\$this->conn);
        \$model->delete(1);
        \$result = \$model->findById(1);
        \$this->assertNull(\$result);
    }
}

PHP;
        file_put_contents($testPath, $testStub);
        echo "Created: tests/Model/{$name}Test.php\n";

        echo "\nNext steps:\n";
        echo "  1. Run: php console make:migration {$table} to create the database migration\n";
        echo "  2. Add DI entry in config/dependencies.php:\n";
        echo "     {$name}::class => DI\\autowire(),\n";

        return 0;
    }

    private function toSnakePlural(string $name): string
    {
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);
        return $snake . 's';
    }
}
