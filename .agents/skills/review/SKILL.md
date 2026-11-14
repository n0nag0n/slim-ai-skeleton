---
name: review
description: Review a change against this project's rules and run composer check. Use when the user asks for a review, asks if the work is done, or is about to open a PR.
---

# Review

Run `composer check` first. It is lint, PHPStan, and tests. Fix failures before commenting on style.

Then check the diff:

- The change does only what was asked. No new abstraction for one caller.
- Routes, middleware, and services are registered in `config/routes.php`, `config/middleware.php`, and `config/dependencies.php`.
- HTML pages came from `php console make:controller`. JSON uses `JsonRenderer` and the envelope in `.agents/skills/add-json-endpoint/SKILL.md`.
- New models and controllers have tests. Tests construct real objects. No mocking framework, no reflection into private state.
- User input goes through `App\Util\Validator`. SQL parameters are bound.
- Production responses do not include exception messages, paths, or SQL.
- Unused imports added by this change are removed. Pre-existing dead code is mentioned, not deleted.
- A convention change updated `AGENTS.md` and the skill that owns the procedure. A user-facing change updated `README.md`.

`php console review:pr <branch> "<message>"` runs the same checks, then commits and opens a PR. Run it only when the user asked for a PR.
