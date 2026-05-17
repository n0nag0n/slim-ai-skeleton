# AGENTS.md — For AI Coding Assistants

## Who This Project Is For

This skeleton is designed for AI coding assistants (like me) to drop into and immediately understand. No ORM magic. No auto-discovery. No layers of indirection. Every file has a clear purpose, every pattern is predictable.

## README vs. AGENTS.md

`README.md` is for humans — plain language, quick start, troubleshooting. `AGENTS.md` is for AI coding assistants — conventions, architecture, testing patterns. Keep them consistent but targeted to their audience. If you add a feature, update both: the README for end-users, AGENTS.md for the next AI that works on the project.

## Core Principles

These are not suggestions. They are the project's identity.

### 1. Simplicity First

Minimum code that solves the problem. Nothing speculative.

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

### 2. Minimal Dependencies

Every dependency is a liability. Before adding one:
- Check if raw PDO or a simple function solves it
- If you must add a dependency, prefer ones with few transitive deps
- Never add a framework for something a 10-line function handles

Current prod deps: slim, slim/psr7, php-di/slim-bridge, phpdotenv, doctrine/dbal, tracy, slim/twig-view. This is the ceiling, not the floor.
Dev deps: phpunit, phpstan, phpcs.

### 3. No Magic

Everything should be traceable. If an AI can't find where something comes from in one grep, it's too hidden.

- No auto-discovery of routes, middleware, or services
- No global functions (NO `env()`, no helper files)
- No superglobals in application code — `$_ENV` only in `config/dependencies.php`, HTTP input through Request object
- No facades, no service location — constructor injection only

### 4. Surgical Changes

Touch only what you must.

- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it — don't delete it.
- When your changes create orphans, clean them up (unused imports, variables). Don't clean up pre-existing dead code unless asked.

### 5. Goal-Driven Execution

Transform every task into verifiable goals.

- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"
- "Build new code" → "Write tests for the new behavior, then make them pass"

State your plan before executing:
```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
```

### 6. Surface Tradeoffs

If something is unclear, stop. Name what's confusing. Ask.

- If multiple interpretations exist, present them — don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- State your assumptions explicitly. If uncertain, ask.

## Architecture at a Glance

### Request Flow

```
public/index.php
  → loads .env, enables Tracy
  → builds PHP-DI container from config/dependencies.php
  → creates Slim app via DI\Bridge\Slim\Bridge
  → applies middleware from config/middleware.php
  → registers routes from config/routes.php
  → adds TracyMiddleware (debug only) — captures request/response for panels
  → adds RoutingMiddleware + BodyParsingMiddleware
  → registers Tracy panels via ExtensionLoader (debug only)
  → sets up error handler (content-negotiated)
  → runs
```

### Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Front controller. Bootstraps everything. |
| `config/dependencies.php` | All DI definitions in one file. |
| `config/routes.php` | ALL routes in one file. Add new routes here. |
| `config/middleware.php` | Middleware stack. TwigMiddleware + app middleware. |
| `migrations/*.sql` | Timestamped SQL files (example files use .sql.example suffix). Run via `php migrate`. |
| `config/console.php` | CLI command definitions. Add new commands here. |
| `src/Controller/*.php` | Request handlers. Each method receives `Request` + returns `Response`. |
| `src/Model/*.php` | DBAL query wrappers. Constructor-inject `Connection`. |
| `src/Debug/TracyMiddleware.php` | Captures PSR-7 request/response into static props for Tracy panels. |
| `src/Debug/DbalQueryLogger.php` | DBAL Driver Middleware — captures query timing/SQL/params for the database panel. |
| `src/Debug/DbalQueries.php` | Query data container shared between DbalQueryLogger and DatabasePanel. |
| `src/Debug/Tracy/*.php` | Tracy bar panels: Request, Response, Routes, Session, Database. |
| `src/Util/*.php` | Utility classes. Pure logic, no HTTP or DB dependencies. |
| `src/Util/Session.php` | Session wrapper. Inject into controllers/services instead of using `$_SESSION`. |
| `src/Util/Validator.php` | Validation utility. Method-chaining with `required()`, `email()`, `minLength()`, etc. |
| `src/Util/Pagination.php` | Pagination helper. Computes offset/limit/totalPages from page + perPage. |
| `src/Console/*.php` | CLI commands. Each implements `CommandInterface`. Registered in `config/console.php`. |
| `src/Renderer/JsonRenderer.php` | JSON response helper. |
| `templates/*.twig` | Twig views. `layout.twig` is the base. |
| `templates/error/*.twig` | Error pages (404, 500). |
| `tests/TestCase.php` | Base test class. Provides `createApp()` and `createRequest()`. |

## Conventions

### Adding a New Route

1. Add the route to `config/routes.php`:
   ```php
   $app->get('/example', [ExampleController::class, 'index']);
   ```
2. Create `src/Controller/ExampleController.php`:
   ```php
   namespace App\Controller;

   use Psr\Http\Message\ResponseInterface;
   use Psr\Http\Message\ServerRequestInterface;

   class ExampleController
   {
       public function __construct(private \Slim\Views\Twig $twig) {}

       public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
       {
           return $this->twig->render($response, 'example.twig');
       }
   }
   ```
3. Create `templates/example.twig`.
4. Write a test in `tests/Controller/ExampleControllerTest.php`.
5. Run `composer lint && composer stan && composer test`.

### Adding a Database Query

1. Add the method to an existing Model or create `src/Model/YourModel.php`.
2. Write test methods in `tests/Model/YourModelTest.php` (extending `App\Test\TestCase`) that insert test data and assert on the query results.

### Adding a Utility Class

Pure logic that doesn't touch HTTP or the database goes in `src/Util/`. Utility classes are plain PHP with no framework dependencies and should be stateless or constructed with simple values.

```php
namespace App\Util;

class Slugger
{
    public function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    }
}
```

No corresponding test is required — test utilities only if they have non-trivial logic.

### Using Sessions

Inject `App\Util\Session` via constructor. The session is auto-started by middleware before controllers run.

```php
class SomeController
{
    public function __construct(private \App\Util\Session $session) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->session->set('user_id', 42);
        $userId = $this->session->get('user_id');
        $this->session->delete('user_id');
        $this->session->regenerate();
        // ...
    }
}
```

Available methods: `get()`, `set()`, `delete()`, `has()`, `clear()`, `all()`, `getId()`, `regenerate()`, `destroy()`, `start()`.

Never use `$_SESSION` directly in application code — always inject `Session`.

### Flash Messages

Flash messages survive for exactly one request (set in one request, read on the next). Use them for form submission feedback:

```php
// Set after a successful form submission (in a POST handler)
$this->flash->set('success', 'Post created successfully.');

// Redirect, then read on the next request (in the GET handler)
$message = $this->flash->get('success'); // auto-deleted after read

if ($this->flash->has('error')) {
    $error = $this->flash->get('error');
}
```

Inject `App\Util\Flash` via constructor. It wraps `Session` internally. Available methods: `set(key, value)`, `get(key, default)`, `has(key)`.

### Validation

Use `App\Util\Validator` for input validation. Method-chaining, no magic:

```php
use App\Util\Validator;

$v = new Validator($request->getParsedBody(), [
    'email' => 'Email address',
]);

$v->required('name', 'email', 'password');
$v->email('email');
$v->minLength('password', 8);
$v->maxLength('name', 255);
$v->matches('password', 'password_confirm');
$v->numeric('age');
$v->inArray('role', ['admin', 'user']);
$v->url('website');

if ($v->fails()) {
    $errors = $v->getErrors();    // ['email' => ['Email address is required.']]
    $first = $v->getFirstError(); // 'Email address is required.'
    return $this->renderer->render($response, ['errors' => $errors], 422);
}
```

All methods return `$this` for chaining. Error messages use the field labels passed in the constructor.

### Pagination

Use `App\Util\Pagination` for list endpoints. It computes offset/limit for SQL queries:

```php
use App\Util\Pagination;

$page = (int) ($request->getQueryParams()['page'] ?? 1);
$perPage = (int) ($request->getQueryParams()['per_page'] ?? 20);

$totalItems = $this->conn->fetchOne('SELECT COUNT(*) FROM posts');
$pagination = new Pagination($totalItems, $page, $perPage);

$posts = $this->conn->fetchAllAssociative(
    'SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?',
    [$pagination->getLimit(), $pagination->getOffset()]
);

return $this->renderer->render($response, [
    'data' => $posts,
    'pagination' => $pagination->toArray(),
]);
```

`toArray()` returns: `page`, `per_page`, `total_items`, `total_pages`, `has_previous`, `has_next`.

### Environment Variables

`$_ENV` is reserved for boot-time wiring in `config/dependencies.php` only.
Never use `$_ENV` in controllers, models, services, or templates.

```php
// config/dependencies.php — OK
Connection::class => function () {
    return DriverManager::getConnection([
        'url' => $_ENV['DATABASE_URL'] ?? 'sqlite:///var/data.sqlite',
    ]);
};
```

For HTTP input (`$_GET`, `$_POST`, `$_SERVER`), use Slim's Request object:

```php
// src/Controller/ExampleController.php — OK
$name = $request->getQueryParams()['name'] ?? 'Guest';
$body = $request->getParsedBody();
$method = $request->getMethod();
```

Tests set `$_ENV` values in `tests/bootstrap.php` before the container is built.

### Type Declarations

Every PHP file must begin with `declare(strict_types=1);` — enforced by phpcs. This eliminates type coercion ambiguity and makes method signatures fully reliable.

```php
<?php

declare(strict_types=1);

namespace App\Util;

class Slugger
{
    public function slugify(string $text): string
    {
        // ...
    }
}
```

Name methods and classes so they are self-documenting. A name like `findByStatus(string $status): array` needs no docblock — the type signature and name say everything. Reserve comments for explaining *why*, not *what*.

### Error Handling

The error handler in `public/index.php`:

- **Debug mode** (`DEBUG_MODE=true`): Throws the exception for Tracy to handle (beautiful debug page).
- **Production**: Content-negotiated. `Accept: text/html` → Twig error page. Otherwise → JSON.

When curling the dev server for debugging, DON'T parse the HTML output. If you need to check an error response in production mode, set `DEBUG_MODE=false` in `.env` and use `curl -H "Accept: application/json"`.

### Migrations

Migrations are timestamped SQL files in `migrations/`. Run them with:

```bash
php migrate
# or
composer migrate
```

The runner tracks executed migrations in a `_migrations` table. SQLite by default.

To add a migration: create `migrations/YYYYMMDD_HHMMSS_description.sql` with raw SQL.

### CLI Commands

The project includes a CLI framework at `php console`. Commands are registered in `config/console.php`:

```bash
php console help                    # List all commands
php console make:controller <Name>  # Scaffold controller + test
php console make:model <Name>       # Scaffold model + migration + test
php console make:migration <desc>   # Create a blank migration file
php console cache:clear             # Clear Twig/DI cache
php console route:list              # Show registered routes
php console sync-ai-instructions    # Sync AGENTS.md to all AI configs
```

To add a new command:

1. Create `src/Console/YourCommand.php` implementing `App\Console\CommandInterface`.
2. Register it in `config/console.php`:
   ```php
   'your:command' => ['class' => YourCommand::class, 'description' => 'What it does'],
   ```
3. The `execute()` method receives `(array $args, Container $container)` and returns an int exit code.

### Syncing AI Configs

`AGENTS.md` is the source of truth. Copies are mirrored to Claude, Copilot, Gemini, Cursor, Windsurf, Continue, and Cline config files. After editing `AGENTS.md`, run:

```bash
composer sync-ai-instructions
```

## Testing

Base test class: `tests/TestCase.php` — provides `createApp()`, `createRequest()`, and `runMigrations()`.

### Testing Controllers

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

Controller tests don't need the database — mock models or test endpoints that don't touch the DB.

### Testing Models

Model tests create their tables inline in `setUp()` since example migrations are `.sql.example` files that aren't auto-run. This keeps each test file self-contained.

```php
class YourModelTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = $this->createApp();
        $this->conn = $this->app->getContainer()->get(Connection::class);
        // Create the table inline — the migration is a .sql.example reference
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

### Test Configuration

Tests use an in-memory SQLite database configured in `tests/bootstrap.php`. The app boots fresh for each test.

### Testing Philosophy

Tests should be straightforward — no mocking frameworks, no reflection workarounds, no over-engineering.

**Reflection in tests is a code smell.** If you need reflection to access private properties or inject test data, the code wasn't designed for testability. Fix the code instead: add a single optional constructor parameter or a setter. One line, no new abstractions.

**Don't add abstractions just to make things testable.** An interface with one implementation or a filesystem wrapper class is worse than the problem it solves. The line between well-factored and over-engineered is crossed when you add your second class to support a single test. Keep it simple:

- Good: an optional constructor parameter with a natural default
- OK: a setter used only in tests
- Too far: extracting interfaces, creating strategy classes, dependency-injecting filesystem wrappers

**Honest integration tests beat fake unit tests.** Commands that write files, run migrations, or touch the network operate on real filesystems and databases. Don't mock those layers — run them against temp directories and in-memory SQLite, then clean up. The tests are slower by microseconds but actually test the real behavior.

After every task, verify nothing is broken:
```bash
composer lint && composer stan && composer test
```

## What I Care About (Selfish Requests)

1. **Flat files, not folders.** I find things faster in a shallow tree.
2. **Tests I can copy-paste.** A good test file is a template for the next 20 tests I'll write. `tests/Model/ExampleModelTest.php` is the model test template.
3. **DBAL, not raw PDO.** Named parameters, query builder, schema introspection — I know this API well.
4. **JSON for debugging.** When you curl the server, use `application/json` accept header. HTML error pages are massive and full of JS/CSS noise.

## Security Standards

These are enforced patterns, not suggestions.

### Never Leak Internals in Production

The production error handler must never expose exception messages, stack traces, file paths, SQL errors, or configuration details to the client. The generic handler in `public/index.php` logs errors server-side via `error_log()` and returns sanitized messages only.

When writing any endpoint:
- Catch exceptions and return generic error messages in production
- Use `$displayErrorDetails` (Slim's `addErrorMiddleware` param) to gate debug info — never hardcode `true` outside of dev
- Never pass `$exception->getMessage()` to templates or JSON responses outside of debug mode

### Output Must Be Encoded

- **JSON**: Always check `json_encode()` return value for `false`. Never assume it will succeed. Provide a fallback.
- **HTML/Twig**: Rely on Twig's auto-escaping (`{{ var }}` escapes, `{{ var\|raw }}` explicitly marks unsafe). Never pass unsanitized user input to templates. Never use `|raw` unless the content is trusted HTML you generated yourself.
- **Debug panels**: Use `htmlspecialchars()` on any value rendered in Tracy panels.

### Sessions Must Be Hardened

The `Session` utility (`src/Util/Session.php`) already configures:
- `HttpOnly` — prevents JavaScript access to the cookie
- `SameSite=Lax` — prevents CSRF from cross-site requests
- `Secure` — only sent over HTTPS (auto-detected from `$_SERVER['HTTPS']`)

When working with sessions:
- Always inject `App\Util\Session` — never touch `$_SESSION` directly
- Regenerate the session ID after privilege escalation (login, role change)
- Destroy sessions on logout

### SQL Injection Is Not Possible Here

All database access goes through Doctrine DBAL's parameterized queries (`?` placeholders or named `:param` parameters). Never concatenate user input into SQL strings. If you must write raw SQL, always use parameterized queries through DBAL's `fetchAllAssociative()`, `executeStatement()`, `insert()`, `update()`, `delete()` methods.

### Security Headers

The `SecurityHeadersMiddleware` (`src/Security/SecurityHeadersMiddleware.php`) is registered in the middleware stack and adds these headers to every response:

| Header | Value |
|--------|-------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `X-XSS-Protection` | `0` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` |
| `Content-Security-Policy` | `default-src 'self'` (with style/img exceptions) |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |

If you need to loosen CSP for a legitimate reason (e.g., embedding a third-party widget), add a comment explaining why and keep the scope as narrow as possible.

### CSRF Protection

`App\Security\CsrfMiddleware` is registered in `public/index.php` after body parsing and protects all `POST`, `PUT`, `DELETE`, and `PATCH` routes. It checks for a token in the `X-CSRF-Token` header first, then falls back to `csrf_token` in the parsed body or form-encoded body.

Use `App\Util\Csrf` in controllers when you need to validate or regenerate tokens:

```php
public function __construct(private \App\Util\Csrf $csrf) {}

// Generate or retrieve the existing token
$token = $this->csrf->generate();

// Validate a token from user input
if (!$this->csrf->validate($inputToken)) {
    // reject
}

// Regenerate after privilege escalation
$this->csrf->regenerate();
```

A `csrf_token` variable is automatically available in all Twig templates for use in forms:

```twig
<form method="post">
    <input type="hidden" name="csrf_token" value="{{ csrf_token }}">
</form>
```

### CORS

`App\Security\CorsMiddleware` is registered in `config/middleware.php` with an allowlist of origins. It handles `OPTIONS` preflight requests and adds `Access-Control-Allow-Origin`, `Access-Control-Allow-Credentials`, and `Access-Control-Expose-Headers` headers.

To add or remove allowed origins, edit the array in `config/middleware.php`:

```php
$app->add(new CorsMiddleware([
    'http://localhost:8080',
    'http://localhost:5173',
]));
```

### Password Hashing

Use PHP's built-in `password_hash()` and `password_verify()` for all password storage — never hashing with `md5()`, `sha1()`, or unsalted algorithms:

```php
// Hashing a password (e.g., during registration)
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verifying a password (e.g., during login)
if (password_verify($password, $storedHash)) {
    // authenticated
}

// Re-hashing if the algorithm or cost has changed since storage
if (password_needs_rehash($storedHash, PASSWORD_BCRYPT, ['cost' => 12])) {
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    // update stored hash
}
```

Never store plain-text passwords. Never use reversible encryption. Never log or expose password values in error messages, debug output, or query logs.

### User Input Validation

Use `App\Util\Validator` for all user-supplied input:
- Always validate on the server — never trust client-side validation alone
- Validate both `$request->getParsedBody()` (POST body) and `$request->getQueryParams()` (query string)
- Use `required()`, `email()`, `minLength()`, `maxLength()`, `matches()`, `numeric()`, `inArray()`, `url()` as appropriate
- Return 422 with error details on validation failure

### File Operations in Commands

Console commands that write files (`make:controller`, `make:model`, `make:migration`) use controlled paths under the project root. User-provided names are embedded in filenames but are constrained by the filesystem (no path traversal via CLI args is possible since the attacker already has shell access). Still, keep filenames safe:
- Strip or replace characters that could be interpreted by the shell
- Never accept absolute paths or `../` sequences from user input

### Debug Tools Are Never in Production

All Tracy-related code (middleware, panels, query logger) is gated behind `DEBUG_MODE=true`. The `dependencies.php` file adds `DbalQueryLogger` only when debug is on. Never remove these guards. If you add a new debug-only feature, wrap it in the same `$debug` check.

### Verification

After any change that touches security-relevant code (error handling, input validation, sessions, authentication, CSP headers), verify:
```bash
composer lint && composer stan && composer test
```
