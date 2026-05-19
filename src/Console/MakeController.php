<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class MakeController implements CommandInterface
{
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

        $root = dirname(__DIR__, 2);

        $lowerName = lcfirst($name);
        $templateDir = $root . '/templates/' . $lowerName;

        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
            echo "Created: templates/{$lowerName}/\n";
        }

        $controllerPath = $root . '/src/Controller/' . $name . 'Controller.php';
        $controllerStub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class {$name}Controller
{
    public function __construct(private \Slim\Views\Twig \$twig) {}

    public function index(ServerRequestInterface \$request, ResponseInterface \$response): ResponseInterface
    {
        return \$this->twig->render(\$response, '{$lowerName}/index.twig');
    }

    public function show(ServerRequestInterface \$request, ResponseInterface \$response, string \$id): ResponseInterface
    {
        return \$this->twig->render(\$response, '{$lowerName}/show.twig', [
            'id' => \$id,
        ]);
    }
}

PHP;
        file_put_contents($controllerPath, $controllerStub);
        echo "Created: src/Controller/{$name}Controller.php\n";

        $indexTwig = $templateDir . '/index.twig';
        $indexStub = <<<TWIG
{% extends 'layout.twig' %}

{% block title %}{$name}{% endblock %}

{% block content %}
<p>List of {$lowerName}.</p>
{% endblock %}

TWIG;
        file_put_contents($indexTwig, $indexStub);
        echo "Created: templates/{$lowerName}/index.twig\n";

        $showTwig = $templateDir . '/show.twig';
        $showStub = <<<TWIG
{% extends 'layout.twig' %}

{% block title %}{$name} #{{ id }}{% endblock %}

{% block content %}
<p>Showing {$lowerName} #{{ id }}.</p>
{% endblock %}

TWIG;
        file_put_contents($showTwig, $showStub);
        echo "Created: templates/{$lowerName}/show.twig\n";

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
        \$request = \$this->createRequest('GET', '/{$lowerName}');
        \$response = \$app->handle(\$request);

        \$this->assertEquals(200, \$response->getStatusCode());
    }

    public function testShowReturns200(): void
    {
        \$app = \$this->createApp();
        \$request = \$this->createRequest('GET', '/{$lowerName}/1');
        \$response = \$app->handle(\$request);

        \$this->assertEquals(200, \$response->getStatusCode());
    }
}

PHP;
        file_put_contents($testPath, $testStub);
        echo "Created: tests/Controller/{$name}ControllerTest.php\n";

        echo "\nNext: Add routes to config/routes.php:\n";
        echo "  \$app->get('/{$lowerName}', [{$name}Controller::class, 'index']);\n";
        echo "  \$app->get('/{$lowerName}/{id}', [{$name}Controller::class, 'show']);\n";

        return 0;
    }
}
