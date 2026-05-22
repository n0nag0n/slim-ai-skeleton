# Source Code Conventions

These rules apply to all files under `src/` and tests under `tests/`.

## Type Declarations

Every PHP file must begin with `declare(strict_types=1);` -- enforced by
phpcs. This eliminates type coercion ambiguity and makes method signatures
fully reliable.

Name methods and classes so they are self-documenting. A name like
`findByStatus(string $status): array` needs no docblock -- the type
signature and name say everything. Reserve comments for explaining *why*,
not *what*.

## Imports

All `use` statements go at the top of the file. Do not use inline or
deferred imports to work around circular dependencies. If a circular
import occurs, the module structure is wrong -- fix the structure instead.

The only acceptable reason for a non-top-level import: the imported module
has expensive load-time side effects and the calling code path is rarely
executed.

## Constructor Injection

No facades, no service location, no static accessors. Inject dependencies
through the constructor only. If `php-di/slim-bridge` can autowire it,
declare the type-hinted dependency and it will arrive ready for use.

```php
class ExampleController
{
    public function __construct(
        private \Slim\Views\Twig $twig,
        private \App\Util\Session $session,
    ) {}
}
```

## No Superglobals

Never use `$_ENV`, `$_GET`, `$_POST`, `$_SERVER`, `$_SESSION`, or
`$_COOKIE` in application code. Use Slim's Request object for HTTP input.

`$_ENV` is reserved for `config/dependencies.php` boot-time wiring only.

## Inline SQL

When writing SQL inside PHP files (models, controllers), always use nowdoc
syntax (`<<<'SQL'`) to avoid PHP string quoting conflicts:

```php
<<<'SQL'
SELECT COUNT(*) as total,
  SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done
FROM tasks WHERE project_id = ?
SQL
```

Nowdoc prevents variable interpolation and eliminates quote escaping errors.
This is the only SQL string convention permitted in `src/`.
