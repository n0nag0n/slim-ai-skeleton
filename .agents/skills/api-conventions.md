# API Response Conventions

When building API endpoints, follow this envelope pattern for predictable
responses:

```
GET /api/resource        -> 200 {"data": {...}}
GET /api/resources       -> 200 {"data": [...], "pagination": {...}}
POST /api/resources      -> 201 {"data": {...}}
POST invalid             -> 422 {"errors": {"field": ["Message"]}}
PUT invalid              -> 422 {"errors": {"field": ["Message"]}}
Server error             -> 500 {"error": "Internal Server Error"}
Not found                -> 404 {"error": "Resource not found"}
```

These are conventions, not enforced by the framework. Controllers return
responses directly -- follow the pattern for consistency.

The `App\Renderer\JsonRenderer` helper can be used to ensure consistent
JSON encoding with error handling:

```php
use App\Renderer\JsonRenderer;

public function __construct(private JsonRenderer $renderer) {}

// Successful response
return $this->renderer->render($response, ['data' => $post], 201);

// Validation errors
return $this->renderer->render($response, ['errors' => $errors], 422);

// Not found
return $this->renderer->render($response, ['error' => 'Not found'], 404);
```

Always check `json_encode()` for `false` when building raw JSON responses
yourself. Provide a fallback to prevent leaking internal state.
