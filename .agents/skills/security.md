# Security Standards

These are enforced patterns, not suggestions.

## Never Leak Internals in Production

The production error handler must never expose exception messages, stack
traces, file paths, SQL errors, or configuration details to the client.
The generic handler in `public/index.php` logs errors server-side and
returns sanitized messages only.

When writing any endpoint:
- Catch exceptions and return generic error messages in production
- Use `$displayErrorDetails` (Slim's `addErrorMiddleware` param) to gate
  debug info -- never hardcode `true` outside of dev
- Never pass `$exception->getMessage()` to templates or JSON responses
  outside of debug mode

## Output Must Be Encoded

- **JSON**: Always check `json_encode()` return value for `false`. Never assume
  it will succeed. Provide a fallback.
- **HTML/Twig**: Rely on Twig's auto-escaping (`{{ var }}` escapes, `|raw`
  explicitly marks unsafe). Never pass unsanitized user input to templates.
- **Debug panels**: Use `htmlspecialchars()` on any value rendered in Tracy.

## Sessions Must Be Hardened

The `Session` utility (`src/Util/Session.php`) already configures:
- `HttpOnly`
- `SameSite=Lax`
- `Secure` (auto-detected from `$_SERVER['HTTPS']`)

When working with sessions:
- Always inject `App\Util\Session` -- never touch `$_SESSION` directly
- Regenerate the session ID after privilege escalation
- Destroy sessions on logout

## SQL Injection Is Not Possible Here

All database access goes through Doctrine DBAL's parameterized queries
(`?` placeholders or named `:param` parameters). Never concatenate user
input into SQL strings. If you must write raw SQL, always use parameterized
queries through DBAL's `fetchAllAssociative()`, `executeStatement()`,
`insert()`, `update()`, `delete()` methods.

## Security Headers

The `SecurityHeadersMiddleware` adds these headers to every response:

| Header | Value |
|--------|-------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `X-XSS-Protection` | `0` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` |
| `Content-Security-Policy` | `default-src 'self'` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |

If you need to loosen CSP for a legitimate reason, add a comment
explaining why and keep the scope as narrow as possible.

## CSRF Protection

`App\Security\CsrfMiddleware` protects all `POST`, `PUT`, `DELETE`, and `PATCH`
routes. It checks for a token in the `X-CSRF-Token` header first, then falls
back to `csrf_token` in the parsed body or form-encoded body.

To exclude specific URL path prefixes from CSRF (e.g. API routes), set
`CSRF_EXCLUDED_PATHS` in `.env`.

Use `App\Util\Csrf` in controllers when you need to validate or regenerate
tokens. A `csrf_token` variable is automatically available in all Twig templates.

## CORS

`App\Security\CorsMiddleware` is registered in `config/middleware.php` with
an allowlist of origins. It handles `OPTIONS` preflight requests and adds
`Access-Control-Allow-Origin`, `Access-Control-Allow-Credentials`, and
`Access-Control-Expose-Headers` headers.

To add or remove allowed origins, set `ALLOWED_ORIGINS` in `.env`.

## Password Hashing

Use PHP's built-in `password_hash()` and `password_verify()` for all password
storage -- never hashing with `md5()`, `sha1()`, or unsalted algorithms.

Never store plain-text passwords. Never use reversible encryption. Never log
or expose password values in error messages, debug output, or query logs.

## File Operations in Commands

Console commands that write files use controlled paths under the project
root. Strip or replace characters that could be interpreted by the shell.
Never accept absolute paths or `../` sequences from user input.

## Debug Tools Are Never in Production

All Tracy-related code (middleware, panels, query logger) is gated behind
`DEBUG_MODE=true`. The `dependencies.php` file adds `DbalQueryLogger` only
when debug is on. Never remove these guards. If you add a new debug-only
feature, wrap it in the same `$debug` check.

## Verification

After any change that touches security-relevant code (error handling, input
validation, sessions, authentication, CSP headers), verify:
```bash
composer lint && composer stan && composer test
```
