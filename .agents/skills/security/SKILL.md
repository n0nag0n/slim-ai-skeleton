---
name: security
description: Apply this project's security rules for errors, encoding, sessions, CSRF, CORS, headers, passwords, and SQL. Use when implementing or changing authentication, forms, cookies, headers, validation, or other security-sensitive code.
---

# Security

## Errors

The handler in `public/index.php` logs the real exception with `error_log()` and returns a generic message outside debug mode. Do not send `$exception->getMessage()`, file paths, or SQL to a template or JSON body unless debug mode is on. Do not hardcode `addErrorMiddleware(true, ...)`.

## Output

`JsonRenderer` checks `json_encode()`. Twig escapes `{{ var }}`. Use `|raw` only for HTML this app generated. Tracy panel values go through `htmlspecialchars($value, ENT_QUOTES)`.

## Session and CSRF

Inject `App\Util\Session`. Do not touch `$_SESSION`. Regenerate the session id after login or a role change. Destroy it on logout.

`CsrfMiddleware` checks `X-CSRF-Token`, then `csrf_token` in the body, for `POST`, `PUT`, `DELETE`, and `PATCH`. Twig already has `csrf_token`. Call `$this->csrf->regenerate()` after privilege changes. Skip path prefixes with `CSRF_EXCLUDED_PATHS` in `.env`.

`tests/TestCase::createApp()` does not register `CsrfMiddleware`. A passing POST test does not prove CSRF works.

## Headers, CORS, passwords

`SecurityHeadersMiddleware` sets `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, a locked `Permissions-Policy`, `Content-Security-Policy: default-src 'self'`, and HSTS. A wider CSP needs a comment and the smallest change that works.

Origins come from `ALLOWED_ORIGINS` in `.env`. The middleware is built in `config/dependencies.php`.

Store passwords with `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` and check them with `password_verify()`. Rehash when `password_needs_rehash()` is true. Do not log passwords.

## SQL and debug

Bound parameters only, through DBAL. Tracy, the query logger, and `X-Dev` run only when `DEBUG_MODE=true`. Keep those guards.

Console commands that write files accept a PascalCase or snake_case name and build a path under the project root. Reject absolute paths and `../`.

After a security change, run `composer check` and `composer security:check`. CVE triage is in `SECURITY.md`.
