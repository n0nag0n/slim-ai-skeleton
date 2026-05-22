Historical rationale for explicit patterns.

Auto-discovery (scanning directories, injecting based on naming conventions)
saves typing but costs traceability. When an AI agent needs to know "where
does this service come from?", the answer should be a single grep away.

Every route is registered manually in `config/routes.php`. Every middleware
is added explicitly in `config/middleware.php`. Every DI definition is in
`config/dependencies.php`. This makes the system 100% traceable without
runtime introspection.

This decision means slightly more boilerplate when adding new features,
but eliminates the "where is this configured?" debugging tax.
