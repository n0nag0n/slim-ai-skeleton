# AGENTS.md

Rules for AI coding tools. `README.md` is the human guide. This file is the map.
Procedures live in skills. Read the matching skill before writing code:

- HTML page, screen, or form → `.agents/skills/add-html-page/SKILL.md`
- JSON or API endpoint → `.agents/skills/add-json-endpoint/SKILL.md`
- Model, table, query, or migration → `.agents/skills/add-model/SKILL.md`
- Review, or "is this done" → `.agents/skills/review/SKILL.md`
- Auth, CSRF, sessions, headers, passwords, or other security-sensitive code → `.agents/skills/security/SKILL.md`

`src/AGENTS.md` applies under `src/`. `tests/AGENTS.md` applies under `tests/`.

After editing this file, run `composer sync-ai-instructions`. It updates the full copies for the provider saved in `.agents/provider`. Pointer files stay as they are.

## What is already in the app

Two routes. `src/Model/ExampleModel.php` and `migrations/*.sql.example` are reference shapes, not a sample feature:

- `GET /` → `HomeController` → `templates/home.twig`
- `GET /health` → `HealthController` → JSON `{"status","time"}`

There is no sample domain. A class or URL named in a skill does not exist until you create it. Do not search the repo for it, and do not route to it.

## Boot order

`public/index.php` loads `.env`, builds the container from `config/dependencies.php`, applies `config/middleware.php`, registers `config/routes.php`, then adds Tracy (debug only), routing, body parsing, and `CsrfMiddleware`. The error handler is content-negotiated in the same file.

`config/middleware.php` is CORS, security headers, session start, the Twig `csrf_token` global, and Twig. CSRF stays in `index.php` because it must run after body parsing. New middleware goes in `config/middleware.php` unless it depends on that order.

## Where new code goes

| Task | Where |
|------|--------|
| URL | `config/routes.php`, above `// @scaffold:routes` |
| HTML page | `php console make:controller Name` |
| JSON handler | `src/Controller/`, `App\Renderer\JsonRenderer` |
| Query | `src/Model/`, constructor-inject `Connection` |
| Migration | `migrations/*.sql`, then `php console migrate` |
| CLI command | `src/Console/` plus one entry in `config/console.php` |
| Pure logic | `src/Util/` |

Route arguments are named method parameters (`string $id`). `array $args` is always empty with php-di/slim-bridge.

`$_ENV` is read only in `config/`, `src/Console/`, and `src/Util/MigrationFileResolver.php`. HTTP input comes from the Request.

## Do not invent

- A second place to register routes, middleware, or services
- An ORM, repository interface, base model, or service class for one caller
- A sample feature you were not asked to build
- An auth system until the user asks for one. Then follow `.agents/skills/security/SKILL.md`

## Changes

- No new Composer dependency unless the user asks for one.
- Edit the code the task needs. Leave the surrounding code as it is.
- Ask when the request can be read more than one way.

## Debugging

With `DEBUG_MODE=true`, `X-Dev: 1` bypasses CSRF and returns compact JSON errors. The header does nothing when `DEBUG_MODE` is false. Never enable debug mode in production.

```bash
curl -H "Accept: application/json" -H "X-Dev: 1" http://localhost:8080/health
```

## Done

`composer check` runs lint, PHPStan, and tests. Done means it exits 0.

`php console ai:provider` asks which AI tool this install uses, saves that choice in `.agents/provider`, and deletes the other instruction files. `composer sync-ai-instructions` rewrites only the full copies that choice keeps. A missing file or `all` updates Gemini, Windsurf, and Cline. It keeps `.agents/skills/` for every choice. `composer create-project` runs it. A non-interactive run keeps every provider file.
