# slim-ai-skeleton

A lightweight Slim 4 skeleton built for AI-assisted development. Minimal dependencies, explicit patterns, low cognitive load.

## Features

- **Slim 4** — PSR-7/15/17 microframework
- **PHP-DI** — autowired dependency injection
- **Doctrine DBAL** — query builder, no ORM
- **Twig** — template engine for HTML views
- **Tracy** — debug bar and error logging in all environments
- **vlucas/phpdotenv** — environment configuration
- **PHPUnit** — testing with bootstrapped container
- **SQLite by default** — zero-config, swap to MySQL/PostgreSQL via `.env`

## Requirements

- PHP 8.2+
- Composer
- SQLite extension (`pdo_sqlite`)

## Quick Start

```bash
cp .env.example .env
composer install
php migrate
composer start
```

Visit `http://localhost:8080`.

## Commands

| Command | Description |
|---------|-------------|
| `composer start` | Start dev server on port 8080 |
| `composer test` | Run PHPUnit tests |
| `composer migrate` | Run pending migrations |

## Environment

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `dev` | `dev` or `production` |
| `DEBUG_MODE` | `true` | Enables Tracy debug bar |
| `DB_DRIVER` | `pdo_sqlite` | Database driver |
| `DB_PATH` | `var/database.sqlite` | SQLite path |

## Project Structure

```
├── config/
│   ├── dependencies.php     # DI container definitions
│   ├── middleware.php        # Middleware stack (Twig, etc.)
│   └── routes.php           # All routes in one file
├── migrations/              # Timestamped SQL migrations
├── public/
│   └── index.php            # Front controller
├── src/
│   ├── Controller/          # Request handlers (Home, Health, etc.)
│   ├── Model/               # DBAL query wrappers
│   └── Renderer/            # Response formatters (JsonRenderer)
├── templates/               # Twig templates
│   └── error/               # Error page templates (404, 500)
└── tests/                   # PHPUnit tests
    ├── bootstrap.php
    ├── TestCase.php         # Base test case with bootstrapped app
    └── Controller/
```

## Error Handling

- **Development** (`DEBUG_MODE=true`): Tracy handles all errors with detailed debug pages.
- **Production**: Content-negotiated — returns Twig error pages for HTML requests, JSON for API requests.

## License

MIT
