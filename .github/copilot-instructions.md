# AGENTS.md -- For AI Coding Assistants

This is canonical context. Treat it as a source of truth about how the system
works today. For historical rationale, see `.notes/`. For specialized
guides (security, API design, code review), see `.agents/skills/`.

## README vs. AGENTS.md

`README.md` is for humans -- plain language, quick start, troubleshooting.
`AGENTS.md` is for AI coding assistants -- conventions, architecture, and
operational directives. Keep them consistent but targeted. Update both when
adding a feature.

## Core Principles

1. **Simplicity First.** Minimum code that solves the problem. Nothing
   speculative. No features beyond what was asked.
2. **Minimal Dependencies.** Prefer raw PDO or a simple function before adding
   a package. Never add a framework for something a 10-line function handles.
3. **No Magic.** Everything should be traceable in one grep. No auto-discovery,
   no global functions, no superglobals in application code, no facades.
4. **Surgical Changes.** Touch only what you must. Match existing style.
   Clean up orphans (unused imports/variables). Don't refactor unrelated code.
5. **Goal-Driven Execution.** Transform every task into verifiable goals.
   State your plan before executing with verify steps.
6. **Surface Tradeoffs.** If something is unclear, name what's confusing and ask.

## Architecture at a Glance

```
public/index.php
  -> loads .env, enables Tracy
  -> builds PHP-DI container from config/dependencies.php
  -> creates Slim app via DI\Bridge\Slim\Bridge
  -> applies middleware from config/middleware.php
  -> registers routes from config/routes.php
  -> adds TracyMiddleware + RoutingMiddleware + BodyParsingMiddleware
  -> registers Tracy panels (debug only)
  -> sets up error handler (content-negotiated)
  -> runs
```

## Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Front controller. Bootstraps everything. |
| `config/dependencies.php` | All DI definitions in one file. |
| `config/routes.php` | **All** routes in one file. Add new routes here. |
| `config/middleware.php` | Middleware stack. |
| `config/console.php` | CLI command definitions. Add new commands here. |
| `migrations/*.sql` | Timestamped SQL files. Run via `php console migrate`. |
| `src/Controller/*.php` | Request handlers. Each receives Request, returns Response. |
| `src/Model/*.php` | DBAL query wrappers. Constructor-inject Connection. |
| `src/Util/*.php` | Stateless utility classes with no framework dependencies. |
| `src/Console/*.php` | CLI commands. Each implements CommandInterface. |
| `tests/TestCase.php` | Base test class. Provides `createApp()` and `createRequest()`. |

## Conventions

### Adding a New Route

1. Add the route to `config/routes.php`:
   ```php
   $app->get('/example', [ExampleController::class, 'index']);
   $app->get('/example/{id}', [ExampleController::class, 'show']);
   ```
2. Create `src/Controller/ExampleController.php`:
   ```php
   namespace App\Controller;
   use Psr\Http\Message\ResponseInterface;
   use Psr\Http\Message\ServerRequestInterface;

   class ExampleController
   {
       public function __construct(private \Slim\Views\Twig \$twig) {}

       public function index(ServerRequestInterface \$request, ResponseInterface \$response): ResponseInterface
       {
           return \$this->twig->render(\$response, 'example.twig');
       }

       // php-di/slim-bridge passes route args as named parameters.
       // do NOT use array \$args = [] -- it will always be empty.
       public function show(ServerRequestInterface \$request, ResponseInterface \$response, string \$id): ResponseInterface
       {
           return \$this->twig->render(\$response, 'example.twig', ['id' => \$id]);
       }
   }
   ```
3. Create `templates/example.twig`.
4. Write a test in `tests/Controller/ExampleControllerTest.php`.
5. Run `composer lint && composer stan && composer test`.

### Adding a Database Query

1. Add the method to an existing Model or create `src/Model/YourModel.php`.
2. Write test methods in `tests/Model/YourModelTest.php` that insert test
data and assert on the query results.

### Adding a Utility Class

Pure logic that doesn't touch HTTP or the database goes in `src/Util/`.
Utility classes are plain PHP with no framework dependencies.

```php
namespace App\Util;

class Slugger
{
    public function slugify(string \$text): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', \$text), '-'));
    }
}
```

No corresponding test is required -- test utilities only if they have
non-trivial logic.

### Using Sessions

Inject `App\Util\Session` via constructor. The session is auto-started
before controllers run.

```php
class SomeController
{
    public function __construct(private \App\Util\Session \$session) {}

    public function index(): void
    {
        \$this->session->set('user_id', 42);
        \$userId = \$this->session->get('user_id');
        \$this->session->regenerate();
    }
}
```

### Flash Messages

Flash messages survive for exactly one request. Inject `App\Util\Flash`.

```php
// Set after a successful form submission (POST handler)
\$this->flash->set('success', 'Post created successfully.');

// Read on the next request (GET handler) -- auto-deleted after read
\$message = \$this->flash->get('success');
```

### Validation

Use `App\Util\Validator` for input validation. Method-chaining, no magic.

```php
use App\Util\Validator;

\$v = new Validator(\$request->getParsedBody(), [
    'email' => 'Email address',
]);

\$v->required('name', 'email', 'password');
\$v->email('email');
\$v->minLength('password', 8);
\$v->maxLength('name', 255);
\$v->matches('password', 'password_confirm');
\$v->numeric('age');
\$v->inArray('role', ['admin', 'user']);
\$v->url('website');

if (\$v->fails()) {
    \$errors = \$v->getErrors();    // ['email' => ['Email address is required.']]
    return \$this->renderer->render(\$response, ['errors' => \$errors], 422);
}
```

All methods return \$this for chaining. Error messages use labels from the
constructor.

### Pagination

Use `App\Util\Pagination` for list endpoints.

```php
use App\Util\Pagination;

\$page = (int) (\$request->getQueryParams()['page'] ?? 1);
\$perPage = (int) (\$request->getQueryParams()['per_page'] ?? 20);

\$totalItems = \$this->conn->fetchOne('SELECT COUNT(*) FROM posts');
\$pagination = new Pagination(\$totalItems, \$page, \$perPage);

\$posts = \$this->conn->fetchAllAssociative(
    'SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?',
    [\$pagination->getLimit(), \$pagination->getOffset()]
);

return \$this->renderer->render(\$response, [
    'data' => \$posts,
    'pagination' => \$pagination->toArray(),
]);
```

`toArray()` returns: `page`, `per_page`, `total_items`, `total_pages`,
`has_previous`, `has_next`.

### Environment Variables

`$_ENV` is reserved for boot-time wiring in `config/dependencies.php` only.
Never use `$_ENV` in controllers, models, services, or templates.

For HTTP input, use Slim's Request object:

```php
\$name = \$request->getQueryParams()['name'] ?? 'Guest';
\$body = \$request->getParsedBody();
\$method = \$request->getMethod();
```

### Error Handling

The error handler in `public/index.php`:

- **Debug mode** (`DEBUG_MODE=true`): Throws exceptions for Tracy. Pass
  `X-Dev: 1` header for compact JSON instead.
- **Production**: Content-negotiated. `Accept: text/html` -> Twig error page.
  Otherwise -> JSON.

When curling for debugging, DON'T parse HTML. Use:
```bash
curl -H "Accept: application/json" -H "X-Dev: 1" http://localhost:8080/path
```

### X-Dev Header

When `DEBUG_MODE=true`, adding `X-Dev: 1` to requests:
- **Bypasses CSRF validation**
- **Forces JSON error responses** (compact `{message,file,line,type,trace}`)

```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -H "X-Dev: 1" \
  -d '{"title":"Test"}'
```

The `X-Dev` header is **only** active when `DEBUG_MODE=true`. In production
it is silently ignored. Never set `DEBUG_MODE=true` in production.

### API Response Conventions

Follow this envelope pattern for predictable API responses:

```
GET /api/resource        -> 200 {"data": {...}}
GET /api/resources       -> 200 {"data": [...], "pagination": {...}}
POST /api/resources      -> 201 {"data": {...}}
POST invalid             -> 422 {"errors": {"field": ["Message"]}}
Server error             -> 500 {"error": "Internal Server Error"}
Not found                -> 404 {"error": "Resource not found"}
```

These are conventions, not enforced by the framework.

### Migrations

Migrations are raw SQL files in `migrations/`. Run with:
```bash
php console migrate
```

Tracks executed migrations in a `_migrations` table.

#### Driver-Specific Overrides

For any migration `001_create_posts.sql`, driver-specific variants run
automatically:

| File | Runs when |
|------|-----------|
| `001_create_posts.sql` | Default / SQLite (always runs if no override matches) |
| `001_create_posts.mysql.sql` | `DB_DRIVER=pdo_mysql` or `pdo_mariadb` |
| `001_create_posts.pgsql.sql` | `DB_DRIVER=pdo_pgsql` |

Keep the base `.sql` SQLite-compatible for unit tests.

### CLI Commands

```bash
php console help                    # List all commands
php console make:controller <Name>  # Scaffold controller + test + templates
php console make:model <Name>       # Scaffold a DBAL model class
php console make:migration <desc>   # Create a blank migration file
php console make:seeder <Name>      # Scaffold a database seeder
php console cache:clear             # Clear Twig/DI cache
php console route:list              # Show registered routes
php console sync-ai-instructions    # Sync AGENTS.md to all AI configs
php console review:pr <branch> <msg>  # Run checks, create branch, commit, PR
php console db:seed                 # Run all database seeders
```

To add a new command:
1. Create `src/Console/YourCommand.php` implementing `CommandInterface`.
2. Register it in `config/console.php`.
3. The `execute()` method receives `(array $args, Container $container)`.

### Syncing AI Configs

`AGENTS.md` is the source of truth. Copies are mirrored to Claude, Copilot,
Gemini, Cursor, Windsurf, Continue, and Cline config files. After editing
`AGENTS.md` (root or nested), run:

```bash
composer sync-ai-instructions
```

## Where Context Lives

This file is **canonical** -- treat it as truth. Other context layers are
intentionally split by scope:

- **`src/AGENTS.md`** -- Source code rules (strict_types, imports, nowdoc SQL,
  constructor injection, no superglobals). Loaded when working under `src/`.
- **`tests/AGENTS.md`** -- Testing patterns (TestCase, two-suite testing,
  inline table setup, testing philosophy). Loaded when working under `tests/`.
- **`.agents/skills/security.md`** -- Security standards (CSP headers, CSRF,
  password hashing, input validation). Load when working on security code.
- **`.agents/skills/api-conventions.md`** -- API response envelope patterns.
  Load when building or reviewing API endpoints.
- **`.agents/skills/code-review.md`** -- Review checklist and automated check
  commands. Load before finishing a task or opening a PR.
- **`.notes/`** -- Historical rationale (why DBAL over ORM, why explicit over
  magic, etc.). Not canon -- read when you need to understand a past decision.

## What I Care About (Selfish Requests)

1. **Flat files, not folders.** I find things faster in a shallow tree.
2. **Tests I can copy-paste.** `tests/Model/ExampleModelTest.php` is the model
test template.
3. **DBAL, not raw PDO.** Named parameters, query builder, schema introspection.
4. **JSON for debugging.** Use `-H "Accept: application/json" -H "X-Dev: 1"`.
HTML Tracy pages are massive noise.

## After Every Task

Verify nothing is broken:
```bash
composer lint && composer stan && composer test
```

Or use the automation:
```bash
php console review:pr <branch-name> "commit message"
```
