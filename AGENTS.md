# AGENTS.md — For AI Coding Assistants

## Who This Project Is For

This skeleton is designed for AI coding assistants (like me) to drop into and immediately understand. No ORM magic. No auto-discovery. No layers of indirection. Every file has a clear purpose, every pattern is predictable.

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
- Check if `$_ENV`, raw PDO, or a simple function solves it
- If you must add a dependency, prefer ones with few transitive deps
- Never add a framework for something a 10-line function handles

Current prod deps: slim, slim/psr7, php-di/slim-bridge, phpdotenv, doctrine/dbal, tracy, slim/twig-view. This is the ceiling, not the floor.

### 3. No Magic

Everything should be traceable. If an AI can't find where something comes from in one grep, it's too hidden.

- No auto-discovery of routes, middleware, or services
- No global functions (NO `env()`, no helper files)
- No implicit configuration — `$_ENV` reads are explicit
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
  → sets up error handler (content-negotiated)
  → runs
```

### Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Front controller. Bootstraps everything. |
| `config/dependencies.php` | All DI definitions in one file. |
| `config/routes.php` | ALL routes in one file. Add new routes here. |
| `config/middleware.php` | Middleware stack. Currently: TwigMiddleware. |
| `migrations/*.sql` | Timestamped SQL files. Run via `php migrate`. |
| `src/Controller/*.php` | Request handlers. Each method receives `Request` + returns `Response`. |
| `src/Model/*.php` | DBAL query wrappers. Constructor-inject `Connection`. |
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
5. Run `composer test`.

### Adding a Database Query

1. Add the method to an existing Model or create `src/Model/YourModel.php`.
2. Extend `tests/Model/YourModelTest.php` from `App\Test\TestCase`.

### Dependency Injection

PHP-DI autowires constructors automatically. Register explicit definitions only when you need a factory:

```php
// config/dependencies.php
return [
    YourService::class => DI\autowire(),  // auto — only if constructor needs nothing special
    Connection::class => function () {    // factory — when you need runtime config
        // ... build connection from $_ENV
    },
];
```

### Environment Variables

Read `$_ENV` directly. No helper functions. No global state.

```php
$value = $_ENV['KEY'] ?? 'default';
```

Tests set `$_ENV` values in `tests/bootstrap.php` before the container is built.

### Error Handling

The error handler in `public/index.php`:

- **Debug mode** (`DEBUG_MODE=true`): Throws the exception for Tracy to handle (beautiful debug page).
- **Production**: Content-negotiated. `Accept: text/html` → Twig error page. Otherwise → JSON.

When curling the dev server for debugging, DON'T parse the HTML output. If you need to check an error response in production mode, set `DEBUG_MODE=false` in `.env` and use `curl -H "Accept: application/json"`.

### Models vs. Controllers

- **Controllers** handle HTTP — parsing requests, calling models, returning responses.
- **Models** handle data — SQL queries via DBAL `Connection`. They never touch HTTP.

### Migrations

Migrations are timestamped SQL files in `migrations/`. Run them with:

```bash
php migrate
# or
composer migrate
```

The runner tracks executed migrations in a `_migrations` table. SQLite by default.

To add a migration: create `migrations/YYYYMMDD_HHMMSS_description.sql` with raw SQL.

### Syncing AI Configs

`AGENTS.md` is the source of truth. Copies are mirrored to Claude, Copilot, Gemini, Cursor, Windsurf, Continue, and Cline config files. After editing `AGENTS.md`, run:

```bash
composer sync-ai-instructions
```

## Testing

Base test class: `tests/TestCase.php`

```php
class YourTest extends TestCase
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

Tests use an in-memory SQLite database configured in `tests/bootstrap.php`. The app boots fresh for each test.

## What I Care About (Selfish Requests)

1. **Flat files, not folders.** I find things faster in a shallow tree.
2. **One convention per concept.** If there's a Model pattern, everything is a Model. Don't mix Repository and Model.
3. **Don't make me search for routes.** Single file. No glob-based discovery, no route attributes on controllers.
4. **Explicit DI.** I want to see what services exist without grepping the entire src/ tree.
5. **Tests I can copy-paste.** A good test file is a template for the next 20 tests I'll write.
6. **DBAL, not raw PDO.** Named parameters, query builder, schema introspection — I know this API well.
7. **JSON for debugging.** When you curl the server, use `application/json` accept header. HTML error pages are massive and full of JS/CSS noise.
