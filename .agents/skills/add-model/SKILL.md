---
name: add-model
description: Add a Doctrine DBAL model, a SQLite migration, and a model test. Use when the user asks for a model, a table, a query, or a migration.
---

# Add a model

`src/Model/ExampleModel.php` is the shape. `posts` is documentation for that class, not a table you should keep using. Create a new model for the request. Do not add features to `ExampleModel`.

1. Run `php console make:model Widget` with a PascalCase name. It writes `src/Model/Widget.php` and `tests/Model/WidgetTest.php`.
2. Run `php console make:migration create_widgets`. Put SQLite-compatible SQL in that file. The base `*.sql` file is what unit tests and SQLite use.
3. A driver-specific file replaces the base file when the driver matches:

| File | Runs when |
|------|-----------|
| `001_create_widgets.sql` | SQLite, and any driver without an override |
| `001_create_widgets.mysql.sql` | `DB_DRIVER=pdo_mysql` or `pdo_mariadb` |
| `001_create_widgets.pgsql.sql` | `DB_DRIVER=pdo_pgsql` |

4. Apply it with `php console migrate` or `composer migrate`.
5. PHP-DI autowires a concrete model. Do not add a `config/dependencies.php` entry for it.
6. Model tests create their table in `setUp()`. Files named `*.sql.example` are not migrations.
7. Run `composer check`.

One-line SQL may be a single-quoted string, as in `ExampleModel`. SQL that spans lines or contains quotes uses a nowdoc:

```php
<<<'SQL'
SELECT id, title
FROM widgets
WHERE status = 'open' AND owner_id = ?
SQL
```

Pass values as bound parameters (`?` or `:name`). Never concatenate request input into SQL.

Use `fetchAllAssociative()`, `fetchAssociative()`, `executeStatement()`, `insert()`, `update()`, and `delete()`.
