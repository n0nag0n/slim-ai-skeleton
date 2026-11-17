<?php

declare(strict_types=1);

use App\Console\CommandInterface;
use App\Console\MakeController;
use App\Console\MakeModel;
use App\Console\MakeMigration;
use App\Console\MakeSeeder;
use App\Console\CacheClear;
use App\Console\RouteList;
use App\Console\SyncAiInstructions;
use App\Console\SelectAiProvider;
use App\Console\Help;
use App\Console\Migrate;
use App\Console\DbSeed;
use App\Console\ReviewAndPr;

return [
    'make:controller' => ['class' => MakeController::class, 'description' => 'Scaffold a new controller + test'],
    'make:model' => ['class' => MakeModel::class, 'description' => 'Scaffold a new model class'],
    'make:migration' => ['class' => MakeMigration::class, 'description' => 'Create a new migration file'],
    'make:seeder' => ['class' => MakeSeeder::class, 'description' => 'Scaffold a new database seeder'],
    'cache:clear' => ['class' => CacheClear::class, 'description' => 'Clear Twig and DI container cache'],
    'route:list' => ['class' => RouteList::class, 'description' => 'List all registered routes'],
    'sync-ai-instructions' => [
        'class' => SyncAiInstructions::class,
        'description' => 'Copy AGENTS.md to Gemini, Windsurf, and Cline',
    ],
    'ai:provider' => [
        'class' => SelectAiProvider::class,
        'description' => 'Keep one AI provider\'s instruction files and remove the rest',
    ],
    'migrate' => ['class' => Migrate::class, 'description' => 'Run pending database migrations'],
    'db:seed' => ['class' => DbSeed::class, 'description' => 'Run all database seeders'],
    'review:pr' => [
        'class' => ReviewAndPr::class,
        'description' => 'Run lint/stan/test, then create branch + commit + PR',
    ],
    'help' => ['class' => Help::class, 'description' => 'Display available commands'],
];
