# Code Review Skill

Apply this skill when asked to review code, before submitting changes,
or when evaluating whether a task is complete.

## Automated Checks (Run These First)

These commands are the minimum bar before any change is considered complete:

```bash
composer lint
composer stan
composer test
```

If any check fails, fix the issues before proceeding. Do not bypass these.

## Review Checklist

### 1. Simplicity
- No features beyond what was asked
- No abstractions for single-use code
- No unnecessary configurability
- If code could be 50 lines instead of 200, suggest rewriting

### 2. Traceability
- Can every import, route, and service be found in one grep?
- Are new dependencies added to `config/dependencies.php` explicitly?
- No superglobals in application code?
- Is HTTP input read through the Request object?

### 3. Type Safety
- Does every PHP file start with `declare(strict_types=1);`?
- Are all method parameters and returns typed?
- No `mixed` or omitted types where a specific type exists

### 4. Test Coverage
- Do new models have corresponding `tests/Model/*Test.php` files?
- Do new controllers have corresponding `tests/Controller/*Test.php` files?
- Are tests straightforward and free of mocking frameworks?
- Is reflection avoided? If reflection is needed, suggest fixing the code
  instead (optional constructor parameter or setter)

### 5. Style Consistency
- Does the code match existing style exactly?
- Are unused imports, variables, or orphans cleaned up?
- Is dead code mentioned but not deleted unless authorized?

### 6. Security
- Is user input validated with `App\Util\Validator`?
- Are parameterized queries used for all SQL?
- No raw user input concatenated into SQL or templates?
- Are exception messages gated from production responses?
- Are session calls routed through the injected `Session` class?

### 7. SQL Conventions
- Are inline SQL strings written with nowdoc syntax (`<<<'SQL'`...) ?
- Are migrations SQLite-compatible by default with driver overrides?

### 8. Documentation
- Is the root `AGENTS.md` updated if conventions changed?
- Is the `README.md` updated if user-facing behavior changed?
- If a new CLI command was added, is it listed in the CLI table?

## After Review

Once the checklist is satisfied, use `php console review:pr <branch> <message>`
to create a branch, commit, push, and open a pull request.
