<?php

use App\Console\CommandInterface;
use App\Console\MakeController;
use App\Console\MakeModel;
use App\Console\MakeMigration;
use App\Console\CacheClear;
use App\Console\RouteList;
use App\Console\SyncAiInstructions;
use App\Console\Help;
use App\Console\Migrate;

return [
    'make:controller' => ['class' => MakeController::class, 'description' => 'Scaffold a new controller + test'],
    'make:model' => ['class' => MakeModel::class, 'description' => 'Scaffold a new model + migration + test'],
    'make:migration' => ['class' => MakeMigration::class, 'description' => 'Create a new migration file'],
    'cache:clear' => ['class' => CacheClear::class, 'description' => 'Clear Twig and DI container cache'],
    'route:list' => ['class' => RouteList::class, 'description' => 'List all registered routes'],
    'sync-ai-instructions' => [
        'class' => SyncAiInstructions::class,
        'description' => 'Sync AGENTS.md to all AI config files',
    ],
    'migrate' => ['class' => Migrate::class, 'description' => 'Run pending database migrations'],
    'help' => ['class' => Help::class, 'description' => 'Display available commands'],
];
