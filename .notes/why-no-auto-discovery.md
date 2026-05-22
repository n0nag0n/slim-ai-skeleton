Historical rationale against auto-discovery.

At the time of skeleton creation, auto-discovery was common in PHP
frameworks (Laravel's route model binding, Symfony's autowiring tags).
We explicitly chose not to use any auto-discovery for routes, middleware,
or services because every trajectory an AI agent takes starts as a
cold read of the codebase. If an agent cannot find where something is
registered in one grep, the project has failed its primary purpose:
predictability for AI assistants.

This also keeps us from accidentally discovering unwanted classes in
production.
