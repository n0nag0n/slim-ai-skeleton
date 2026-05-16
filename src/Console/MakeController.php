<?php

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

        // Create controller
        $controllerPath = $root . '/src/Controller/' . $name . 'Controller.php';
        $controllerStub = <<<PHP
<?php

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
}

PHP;
        file_put_contents($controllerPath, $controllerStub);
        echo "Created: src/Controller/{$name}Controller.php\n";

        // Create test
        $testPath = $root . '/tests/Controller/' . $name . 'ControllerTest.php';
        $testStub = <<<PHP
<?php

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
}

PHP;
        file_put_contents($testPath, $testStub);
        echo "Created: tests/Controller/{$name}ControllerTest.php\n";

        echo "\nNext: Add route to config/routes.php:\n";
        echo "  \$app->get('/{$lowerName}', [{$name}Controller::class, 'index']);\n";

        return 0;
    }
}
