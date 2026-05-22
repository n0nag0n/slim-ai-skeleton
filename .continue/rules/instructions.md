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
Dev deps: phpunit, phpstan, phpcs, spaze/phpstan-disallowed-calls, enlightn/security-checker.

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

`$_ENV` is reserved for boot-time wiring — only use it in `config/*` files, `src/Console/*` commands, and `src/Util/MigrationFileResolver.php`. Enforced by PHPStan's disallowed-calls rules.
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
composer security:check            # Scan composer.lock for known CVEs
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

The `X-Dev: 1` request header is a debug-only shortcut that makes API testing faster:

- **Bypasses CSRF validation** — send POST/PUT/DELETE without a token
- **Forces JSON error responses** — no Tracy HTML pages, compact JSON with file/line/trace
- **Activates only when `DEBUG_MODE=true`** — completely ignored in production

Usage:

```bash
# Debug an API endpoint with JSON error responses + CSRF bypass
curl -X POST http://localhost:8080/api/resource \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Dev: 1" \
  -d '{"title":"test"}'
```

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

### Static Security Analysis

PHPStan (via `spaze/phpstan-disallowed-calls`) enforces security rules at `composer stan` time:

| Rule | What it catches | Example message |
|------|----------------|-----------------|
| **Superglobals** | `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_FILES`, `$_SERVER`, `$GLOBALS` in `src/` | "use $request->getQueryParams() instead" |
| **`$_SESSION`** | Direct session access outside the `Session` wrapper | "use injected App\Util\Session instead" |
| **`$_ENV`** | Env access outside `config/`, `Console/`, `MigrationFileResolver.php` | "use $_ENV only in config/" |
| **Dangerous functions** | `eval`, `create_function`, `extract`, `var_dump`, `phpinfo` | "eval is evil" |
| **Code execution** | `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen` | "disallowed function" |
| **Weak hashing** | `md5`, `sha1`, `rand`, `mt_rand` | "use hash() with SHA-256 or password_hash()" |
| **Raw SQL** | `mysql_query`, `mysqli_query`, `PDO::query`, `PDO::exec`, `PDO::prepare` | "use Doctrine DBAL parameterized queries" |
| **Loose calls** | `in_array()` without `$strict`, `htmlspecialchars()` without `ENT_QUOTES` | "set third parameter to true" |

The bundled configs included in `phpstan.neon.dist` cover the common cases. The rules also support `allowIn` and `allowInMethods` for legitimate exceptions (e.g. `$_ENV` in config files, `$_SESSION` in the Session wrapper).

Additionally, `composer security:check` scans `composer.lock` against the Security Advisories database for known CVEs in dependencies. See `SECURITY.md` for the vulnerability triage workflow.

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

To exclude specific URL path prefixes from CSRF (e.g. API routes), set `CSRF_EXCLUDED_PATHS` in `.env`:

```
CSRF_EXCLUDED_PATHS=/api,/health
```

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

To add or remove allowed origins, set the `ALLOWED_ORIGINS` environment variable in `.env`:

```
ALLOWED_ORIGINS=http://localhost:8080,http://localhost:5173
```

The middleware reads this env var at boot time via `config/dependencies.php`.

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
composer lint && composer stan && composer test && composer security:check
```

Or use the automation:
```bash
php console review:pr <branch-name> "commit message"
```

If `security:check` reports CVEs, follow the triage process in `SECURITY.md`.
