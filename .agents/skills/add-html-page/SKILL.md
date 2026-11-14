---
name: add-html-page
description: Add an HTML page with a Slim route, Twig template, and controller test. Use when the user asks to add a page, a screen, a form, or a URL that returns HTML.
---

# Add an HTML page

The app has no sample page to copy. `HomeController` and `templates/home.twig` are the only HTML page. Code named below is not in the repository until this procedure creates it.

1. Run `php console make:controller Widget` with a PascalCase name.
2. The command writes:
   - `src/Controller/WidgetController.php`
   - `templates/widget.twig`
   - `templates/widget-show.twig`
   - `tests/Controller/WidgetControllerTest.php`
   - two routes in `config/routes.php`, above `// @scaffold:routes`
   - A multi-word name is kebab-case in the URL and template. `BlogPost` is `/blog-post`.
3. Running it again for the same name stops and leaves the existing controller in place.
4. Edit those files for the request. Keep route placeholders as named parameters (`string $id`). Do not add `array $args`.
5. Templates extend `layout.twig`. `{{ var }}` is escaped. Use `|raw` only for HTML this app generated.
6. A form posts `csrf_token`:

```twig
<form method="post">
    <input type="hidden" name="csrf_token" value="{{ csrf_token }}">
</form>
```

7. After a successful POST, set a flash message, redirect, and read it on the next GET:

```php
$this->flash->set('success', 'Saved.');
$message = $this->flash->get('success');
```

Inject `App\Util\Flash` and `App\Util\Session`. Do not touch `$_SESSION`.

8. Validate input with `App\Util\Validator` before using it. On failure, re-render the form with the errors.
9. Run `composer check`.

`tests/TestCase::createApp()` does not register `CsrfMiddleware`. A passing POST test does not prove the route accepts a CSRF token. Change the test when you change a path.
