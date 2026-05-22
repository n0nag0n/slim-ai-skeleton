# slim-ai-skeleton

[![PHP Version](https://img.shields.io/packagist/php-v/n0nag0n/slim-ai-skeleton)](https://packagist.org/packages/n0nag0n/slim-ai-skeleton)
[![Packagist Version](https://img.shields.io/packagist/v/n0nag0n/slim-ai-skeleton)](https://packagist.org/packages/n0nag0n/slim-ai-skeleton)
[![Packagist Downloads](https://img.shields.io/packagist/dt/n0nag0n/slim-ai-skeleton)](https://packagist.org/packages/n0nag0n/slim-ai-skeleton)
[![License](https://img.shields.io/github/license/n0nag0n/slim-ai-skeleton)](LICENSE)
[![GitHub Stars](https://img.shields.io/github/stars/n0nag0n/slim-ai-skeleton)](https://github.com/n0nag0n/slim-ai-skeleton)
[![Last Commit](https://img.shields.io/github/last-commit/n0nag0n/slim-ai-skeleton)](https://github.com/n0nag0n/slim-ai-skeleton)
[![AI Friendly](https://img.shields.io/badge/AI-Friendly-5B5BD7?logo=openai&logoColor=white)](AGENTS.md)
[![CI](https://img.shields.io/github/actions/workflow/status/n0nag0n/slim-ai-skeleton/ci.yml?branch=master)](.github/workflows/ci.yml)

A starter project for building PHP web apps with the [Slim 4](https://www.slimframework.com/) framework.
It's designed to work well with AI coding tools (Cursor, Claude Code, Windsurf, etc.) — the code is
simple, explicit, and easy for both humans and AIs to understand.

## What you get

A working web app out of the box — it serves a homepage, a health-check endpoint, and has a database
ready to go. Everything is set up so you can start adding your own pages and features immediately.

## Features

- **Slim 4** — a lightweight PHP framework for handling web requests and responses
- **PHP-DI** — automatic dependency injection container
- **Doctrine DBAL** — database query layer with parameterized queries
- **Twig** — a template system for building HTML pages separate from your PHP code
- **Session handling** — `App\Util\Session` wraps PHP's native sessions and is injectable via constructor
- **Flash messages** — one-request survival messages for form submission feedback via `App\Util\Flash`
- **Validation** — method-chaining input validation with `App\Util\Validator` (required, email, length, matches, etc.)
- **Pagination** — offset/limit calculator with `App\Util\Pagination` for list endpoints
- **CLI console** — `php console` with commands to scaffold controllers, models, migrations, and more
- **Tracy** — a debug bar with panels for requests, responses, routes, sessions, and database queries
- **PHPUnit** — testing framework to make sure your code works
- **PHPStan** — static analysis to catch bugs before runtime
- **PHP_CodeSniffer** — enforces consistent code style
- **SQLite by default** — file-based database, no need for MySQL or PostgreSQL. Swap to MariaDB/MySQL/PostgreSQL anytime — Docker compose files are preconfigured.
- **Security static analysis** — PHPStan rules (via `spaze/phpstan-disallowed-calls`) ban superglobals, dangerous functions, weak hashing, and raw query methods in `src/`
- **Dependency vulnerability scanning** — `composer security:check` scans `composer.lock` against the Security Advisories database for known CVEs

## How AI fits in

AI coding tools work best when code is predictable and easy to trace. This project follows patterns
that AIs recognize:

- **All routes in one file** (`config/routes.php`) — the AI always knows where to add a new URL
- **No magic** — every import is explicit, no auto-discovery, no hidden configuration
- **One file per concept** — controllers handle HTTP, models handle data, templates handle HTML

If you're using an AI coding assistant, just open this project in your editor and the AI will
already understand the patterns. The file `AGENTS.md` contains detailed instructions for AI tools.

## Requirements

- PHP 8.2 or higher
- [Composer](https://getcomposer.org/)
- SQLite extension (`pdo_sqlite`) — usually included with PHP by default, no database server needed

> **Want MariaDB/MySQL or PostgreSQL?** The `.env.example` has connection settings and a `docker-compose.yml` is included to spin up a MariaDB instance. Switch `DB_DRIVER` in `.env` and you're set.

## Quick Start

```bash
composer create-project n0nag0n/slim-ai-skeleton my-project
cd my-project
composer migrate
composer start
```

Open `http://localhost:8080` in your browser. You should see the homepage.

## Commands

| Command | Description |
|---------|-------------|
| `composer start` | Start the development server on port 8080 |
| `composer test` | Run all tests |
| `composer lint` | Check code style with PHP_CodeSniffer |
| `composer stan` | Run PHPStan static analysis |
| `composer cs-fix` | Auto-fix code style issues |
| `composer migrate` | Run pending database migrations |
| `composer security:check` | Scan dependencies for known vulnerabilities |
| `php console` | List all CLI commands (scaffolding, cache, routes) |
| `composer sync-ai-instructions` | Sync AGENTS.md to AI tool config files |

## Your first new page

Here's how to add a new page at `/hello`:

1. **Add a route** in `config/routes.php`:
   ```php
   $app->get('/hello', [App\Controller\HelloController::class, 'index']);
   ```

2. **Create a controller** at `src/Controller/HelloController.php`:
   ```php
   <?php

   namespace App\Controller;

   use Psr\Http\Message\ResponseInterface;
   use Psr\Http\Message\ServerRequestInterface;
   use Slim\Views\Twig;

   class HelloController
   {
       public function __construct(private Twig $twig) {}

       public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
       {
           return $this->twig->render($response, 'hello.twig');
       }
   }
   ```

3. **Create a template** at `templates/hello.twig`:
   ```twig
   {% extends 'layout.twig' %}

   {% block content %}
       <h1>Hello, world!</h1>
   {% endblock %}
   ```

4. **Restart the server** and visit `http://localhost:8080/hello`.

## Project Structure

```
├── config/
│   ├── console.php           # CLI command definitions
│   ├── dependencies.php      # Service definitions for the DI container
│   ├── middleware.php         # Middleware stack (runs on every request)
│   └── routes.php            # ALL routes in one file — add new pages here
├── migrations/               # Database migration files (SQL)
├── public/
│   └── index.php             # Entry point — every request goes through here
├── src/
│   ├── Console/              # CLI commands (scaffolding, cache, routes)
│   ├── Controller/           # Request handlers — one file per page or feature
│   ├── Debug/                # Tracy debug panels and query logging
│   ├── Model/                # Database query code
│   ├── Renderer/             # Response helpers (e.g., JSON)
│   └── Util/                 # Utility classes — Sessions, Flash, Validation, Pagination
├── templates/                # Twig HTML templates
│   └── error/                # Error pages (404, 500)
└── tests/                    # PHPUnit tests
    ├── bootstrap.php
    ├── TestCase.php          # Base test your tests extend from
    ├── Controller/
    ├── Model/
    ├── Renderer/
    └── Util/
```

## Environment Variables

| Variable | Default | What it does |
|----------|---------|-------------|
| `APP_ENV` | `dev` | Set to `production` when deploying |
| `DEBUG_MODE` | `true` | Shows the Tracy debug bar. Turn off in production. |
| `DB_DRIVER` | `pdo_sqlite` | Database driver (`pdo_mysql`, `pdo_pgsql`, etc.) |
| `DB_PATH` | `var/database.sqlite` | Where the SQLite database file lives |
| `CSRF_EXCLUDED_PATHS` | — | Comma-separated URL path prefixes to skip CSRF checks (e.g. `/api,/health`) |
| `ALLOWED_ORIGINS` | `http://localhost:8080` | Comma-separated CORS allowed origins |

## Error Handling

- **Development** (`DEBUG_MODE=true`): Tracy shows a detailed error page with stack traces. Pass `X-Dev: 1` header to get compact JSON with trace details instead.
- **Production** (`DEBUG_MODE=false`): Shows a friendly error page (HTML for browsers, JSON for API calls).

## Debugging

When `DEBUG_MODE=true`, add the `X-Dev: 1` header to your requests for debugging convenience:

- **Bypasses CSRF** — no need to generate tokens during API testing
- **Forces JSON errors** — compact `{message, file, line, type, trace}` responses instead of Tracy HTML

```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -H "X-Dev: 1" \
  -d '{"title":"Test"}'
```

The `X-Dev` header is silently ignored in production (`DEBUG_MODE=false`).

## Migrations

Migrations are raw SQL files in `migrations/`. Run them with:

```bash
composer migrate
```

To add driver-specific variants (e.g. MariaDB `LONGTEXT` vs SQLite `TEXT`), create a `.mysql.sql` or `.pgsql.sql` copy alongside the base `.sql` migration. The runner picks the right one for your `DB_DRIVER`.

Migrations can also connect to MariaDB or PostgreSQL — see the `.env.example` for connection settings. A `docker-compose.yml` is included to run MariaDB locally.

## Troubleshooting

**Port 8080 is already in use**
```
composer start
# Error: Address already in use
```
Use a different port: `php -S localhost:8081 -t public`

**`composer migrate` fails**
Make sure the `var/` directory is writable. SQLite creates the database file there.

**Nothing shows up at localhost:8080**
The server needs to keep running in your terminal. Open a second terminal or use the `-t` flag
to run it in the background: `php -S localhost:8080 -t public > /dev/null 2>&1 &`

## What's next?

- Try adding your own page using the walkthrough above
- Run `composer test` to confirm everything still works after your changes
- Ask your AI assistant: "Add a page that shows the current time"
- When you're ready to deploy, set `DEBUG_MODE=false` and `APP_ENV=production` in `.env`

## Security

See `SECURITY.md` for the vulnerability triage workflow when `composer security:check` reports a CVE.

## License

MIT
