<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class MakeController implements CommandInterface
{
    public function __construct(private ?string $projectRoot = null)
    {
    }

    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "Usage: php console make:controller <Name>\n";
            return 1;
        }

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            echo "Name must be PascalCase, such as Widget.\n";
            return 1;
        }

        $root = $this->projectRoot ?? dirname(__DIR__, 2);
        $slug = $this->toKebab($name);
        $controllerPath = $root . '/src/Controller/' . $name . 'Controller.php';
        if (is_file($controllerPath)) {
            echo "Controller already exists: src/Controller/{$name}Controller.php\n";
            return 1;
        }

        $routesPath = $root . '/config/routes.php';
        $routes = file_get_contents($routesPath);
        if ($routes === false || !str_contains($routes, '    // @scaffold:routes')) {
            echo "Could not find // @scaffold:routes in config/routes.php\n";
            return 1;
        }

        $updated = $this->insertUse($routes, $name);
        if ($updated === null) {
            echo "Could not insert use App\\Controller\\{$name}Controller in config/routes.php\n";
            return 1;
        }

        $scaffold = "    // scaffold:{$name}\n";
        if (!str_contains($updated, $scaffold)) {
            $block = <<<PHP
    // scaffold:{$name}
    \$app->get('/{$slug}', [{$name}Controller::class, 'index']);
    \$app->get('/{$slug}/{id}', [{$name}Controller::class, 'show']);

PHP;
            $updated = str_replace('    // @scaffold:routes', $block . '    // @scaffold:routes', $updated);
        }

        $controllerStub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class {$name}Controller
{
    public function __construct(private Twig \$twig)
    {
    }

    public function index(ServerRequestInterface \$request, ResponseInterface \$response): ResponseInterface
    {
        return \$this->twig->render(\$response, '{$slug}.twig');
    }

    public function show(ServerRequestInterface \$request, ResponseInterface \$response, string \$id): ResponseInterface
    {
        return \$this->twig->render(\$response, '{$slug}-show.twig', [
            'id' => \$id,
        ]);
    }
}

PHP;
        file_put_contents($controllerPath, $controllerStub);
        echo "Created: src/Controller/{$name}Controller.php\n";

        $this->writeTemplate(
            $root . '/templates/' . $slug . '.twig',
            $name,
            "<p>List of {$slug}.</p>\n"
        );
        $this->writeTemplate(
            $root . '/templates/' . $slug . '-show.twig',
            $name . ' #{{ id }}',
            "<p>Showing {$slug} #{{ id }}.</p>\n"
        );

        $testPath = $root . '/tests/Controller/' . $name . 'ControllerTest.php';
        $testStub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Test\Controller;

use App\Test\TestCase;

class {$name}ControllerTest extends TestCase
{
    public function testIndexReturns200(): void
    {
        \$app = \$this->createApp();
        \$request = \$this->createRequest('GET', '/{$slug}');
        \$response = \$app->handle(\$request);

        \$this->assertEquals(200, \$response->getStatusCode());
    }

    public function testShowReturns200(): void
    {
        \$app = \$this->createApp();
        \$request = \$this->createRequest('GET', '/{$slug}/1');
        \$response = \$app->handle(\$request);

        \$this->assertEquals(200, \$response->getStatusCode());
    }
}

PHP;
        file_put_contents($testPath, $testStub);
        echo "Created: tests/Controller/{$name}ControllerTest.php\n";

        file_put_contents($routesPath, $updated);
        echo "Registered: config/routes.php\n";

        return 0;
    }

    private function toKebab(string $name): string
    {
        $kebab = preg_replace('/(?<!^)[A-Z]/', '-$0', $name);
        return strtolower($kebab ?? $name);
    }

    private function insertUse(string $routes, string $name): ?string
    {
        $useLine = "use App\\Controller\\{$name}Controller;\n";
        if (str_contains($routes, $useLine)) {
            return $routes;
        }

        $count = 0;
        $updated = preg_replace(
            '/(use App\\\\Controller\\\\[A-Za-z0-9]+;\n)(\r?\nreturn )/',
            '$1' . $useLine . '$2',
            $routes,
            1,
            $count
        );
        if (is_string($updated) && $count === 1) {
            return $updated;
        }

        $count = 0;
        $updated = preg_replace(
            '/(\r?\n)(return (?:static )?function )/',
            '$1' . $useLine . '$1$2',
            $routes,
            1,
            $count
        );
        if (is_string($updated) && $count === 1) {
            return $updated;
        }

        if (preg_match('/\Areturn (?:static )?function /', $routes) === 1) {
            return $useLine . $routes;
        }

        return null;
    }

    private function writeTemplate(string $path, string $title, string $body): void
    {
        $stub = <<<TWIG
{% extends 'layout.twig' %}

{% block title %}{$title}{% endblock %}

{% block content %}
{$body}{% endblock %}

TWIG;
        file_put_contents($path, $stub);
        echo 'Created: templates/' . basename($path) . "\n";
    }
}
