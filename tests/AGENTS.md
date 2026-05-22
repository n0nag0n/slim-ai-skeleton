# Testing

Base test class: `tests/TestCase.php` -- provides `createApp()`,
`createRequest()`, and `runMigrations()`.

## Two-Suite Testing

| Suite | Command | DB | Speed |
|-------|---------|-----|-------|
| **Unit** | `composer test` | SQLite in-memory | Fast |
| **Integration** | `composer test:integration` | MariaDB (docker) | Slow |

Unit tests are the default. They run against an in-memory SQLite database
and are fast enough for TDD.

Integration tests run against a real MariaDB database via Docker and catch
DB-specific issues that SQLite can't. Only use when SQLite isn't sufficient.

## Testing Controllers

```php
class YourControllerTest extends TestCase
{
    public function testSomething(): void
    {
        $app = $this->createApp();
        $request = $this->createRequest('GET', '/path');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

Controller tests don't need the database -- mock models or test endpoints
that don't touch the DB.

## Testing Models

Model tests create their tables inline in `setUp()` since migrations are
`.sql.example` files that aren't auto-run. This keeps each test file
self-contained.

```php
class YourModelTest extends TestCase
{
    protected Connection $conn;

    protected function setUp(): void
    {
        $this->app = $this->createApp();
        $this->conn = $this->app->getContainer()->get(Connection::class);
        $this->conn->executeStatement('CREATE TABLE your_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function testFindAll(): void
    {
        $this->conn->insert('your_table', ['name' => 'Test']);
        $model = new ExampleModel($this->conn);
        $results = $model->findAll();
        $this->assertCount(1, $results);
    }
}
```

## Adding Integration Tests

Integration tests live in `tests/integration/`. They run against the real
database configured in `phpunit.integration.xml`.

```php
namespace App\Test\Integration;

class YourModelIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = $this->createApp();
        $this->conn = $this->app->getContainer()->get(Connection::class);
        $this->conn->executeStatement('CREATE TABLE your_table (...)');
    }

    public function testFindAllWithRealDb(): void
    {
        // test against real MariaDB
    }
}
```

Run integration tests:
```bash
composer test:integration
```

Or run everything:
```bash
composer test:all
```

## Testing Philosophy

Tests should be straightforward -- no mocking frameworks, no reflection
workarounds, no over-engineering.

**Reflection in tests is a code smell.** If you need reflection to access
private properties or inject test data, the code wasn't designed for
testability. Fix the code instead: add a single optional constructor
parameter or a setter.

**Don't add abstractions just to make things testable.** An interface with
one implementation is worse than the problem it solves.

**Honest integration tests beat fake unit tests.** Run commands against real
filesystems and databases when needed.

After every task, verify nothing is broken:
```bash
composer lint && composer stan && composer test
```
