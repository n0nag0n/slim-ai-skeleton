Historical rationale for database access choices.

When we evaluated ORMs (Eloquent, Doctrine ORM), we found they introduced
magic that made code harder for both humans and AI agents to trace. Queries
hid behind relations, eager loading was implicit, and migrations were abstracted
away from raw SQL.

DBAL gives us named parameters, schema introspection, and query builder
capabilities without object hydration or entity managers. Every query is
visible in the Model file. An agent can grep for `fetchAllAssociative` and
find every database call site immediately.

This decision was made in the initial skeleton design and is not expected
to change.
