---
name: add-json-endpoint
description: Add a JSON API endpoint with JsonRenderer and the response envelope. Use when the user asks for an API, a JSON route, or an /api resource.
---

# Add a JSON endpoint

`GET /health` returns `{"status","time"}`. That is the liveness check. New JSON endpoints use the envelope below. The controller in this skill is not in the repository. Create it. Do not look for an existing resource controller to extend.

Register the route in `config/routes.php` above `// @scaffold:routes`.

```php
$app->post('/api/tasks', [TaskController::class, 'create']);
```

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Renderer\JsonRenderer;
use App\Util\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TaskController
{
    public function __construct(private JsonRenderer $renderer)
    {
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $v = new Validator($body, ['title' => 'Title']);
        $v->required('title');
        $v->maxLength('title', 255);

        if ($v->fails()) {
            return $this->renderer->render($response, ['errors' => $v->getErrors()], 422);
        }

        return $this->renderer->render($response, ['data' => ['title' => $body['title']]], 201);
    }
}
```

Envelope:

```
GET one     200 {"data": {...}}
GET list    200 {"data": [...], "pagination": {...}}
POST        201 {"data": {...}}
invalid     422 {"errors": {"field": ["Message"]}}
missing     404 {"error": "Resource not found"}
failure     500 {"error": "Internal Server Error"}
```

`JsonRenderer` checks `json_encode()` and falls back when it fails. Use it for every JSON body.

List endpoints use `App\Util\Pagination`. `toArray()` returns `page`, `per_page`, `total_items`, `total_pages`, `has_previous`, `has_next`.

CSRF covers `POST`, `PUT`, `DELETE`, and `PATCH`. To skip prefixes such as `/api`, set `CSRF_EXCLUDED_PATHS` in `.env`. With `DEBUG_MODE=true`, `X-Dev: 1` skips CSRF for that request.

Add `tests/Controller/TaskControllerTest.php`. Assert the status code and the envelope keys. Run `composer check`.

For queries, open `.agents/skills/add-model/SKILL.md`. For error leakage, sessions, and headers, open `.agents/skills/security/SKILL.md`.
