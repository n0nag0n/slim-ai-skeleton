<?php

declare(strict_types=1);

namespace App\Test\Console;

use App\Console\MakeController;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class MakeControllerTest extends TestCase
{
    private string $root;
    private string $routesPath;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/slim_test_controller_' . uniqid();
        mkdir($this->root . '/config', 0755, true);
        mkdir($this->root . '/src/Controller', 0755, true);
        mkdir($this->root . '/templates', 0755, true);
        mkdir($this->root . '/tests/Controller', 0755, true);
        $this->routesPath = $this->root . '/config/routes.php';
        file_put_contents($this->routesPath, $this->routesFixture());
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    public function testCreatesControllerTemplatesAndRoutes(): void
    {
        $name = 'Widget';
        $exitCode = $this->runCommand($name);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->root . '/src/Controller/WidgetController.php');
        $this->assertFileExists($this->root . '/tests/Controller/WidgetControllerTest.php');
        $this->assertFileExists($this->root . '/templates/widget.twig');
        $this->assertFileExists($this->root . '/templates/widget-show.twig');

        $routes = (string) file_get_contents($this->routesPath);
        $this->assertStringContainsString('use App\\Controller\\WidgetController;', $routes);
        $this->assertStringContainsString("'/widget'", $routes);
        $this->assertStringContainsString("'/widget/{id}'", $routes);
        $this->assertSame(1, substr_count($routes, '// scaffold:Widget'));
    }

    public function testUsesKebabCaseUrls(): void
    {
        $exitCode = $this->runCommand('BlogPost');

        $this->assertSame(0, $exitCode);
        $routes = (string) file_get_contents($this->routesPath);
        $this->assertStringContainsString("'/blog-post'", $routes);
        $this->assertStringContainsString("'/blog-post/{id}'", $routes);
        $this->assertFileExists($this->root . '/templates/blog-post.twig');
        $controller = (string) file_get_contents($this->root . '/src/Controller/BlogPostController.php');
        $this->assertStringContainsString('blog-post.twig', $controller);
    }

    public function testRefusesWhenControllerExists(): void
    {
        $this->assertSame(0, $this->runCommand('Widget'));
        $routes = (string) file_get_contents($this->routesPath);

        $exitCode = $this->runCommand('Widget');

        $this->assertSame(1, $exitCode);
        $this->assertSame($routes, (string) file_get_contents($this->routesPath));
    }

    public function testInsertsUseBeforeReturnWhenPatternDiffers(): void
    {
        $fixture = <<<'PHP'
<?php

declare(strict_types=1);

return function (App $app) {
    // @scaffold:routes
};

PHP;
        file_put_contents($this->routesPath, $fixture);

        $this->assertSame(0, $this->runCommand('Widget'));
        $routes = (string) file_get_contents($this->routesPath);
        $this->assertStringContainsString("use App\\Controller\\WidgetController;\n", $routes);
        $this->assertLessThan(
            strpos($routes, 'return function'),
            strpos($routes, 'use App\\Controller\\WidgetController;')
        );
    }

    public function testFailsWhenUseCannotBeInserted(): void
    {
        file_put_contents($this->routesPath, "<?php\n\n    // @scaffold:routes\n");

        $exitCode = $this->runCommand('Widget');

        $this->assertSame(1, $exitCode);
        $this->assertFileDoesNotExist($this->root . '/src/Controller/WidgetController.php');
        $this->assertStringNotContainsString('WidgetController', (string) file_get_contents($this->routesPath));
    }

    public function testReturnsErrorWithoutName(): void
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeController($this->root);

        ob_start();
        $exitCode = $command->execute([], $container);
        ob_end_clean();

        $this->assertSame(1, $exitCode);
    }

    public function testRejectsUnsafeName(): void
    {
        $exitCode = $this->runCommand('../Widget');

        $this->assertSame(1, $exitCode);
        $this->assertFileDoesNotExist($this->root . '/src/Controller/WidgetController.php');
    }

    private function runCommand(string $name): int
    {
        $container = (new ContainerBuilder())->build();
        $command = new MakeController($this->root);

        ob_start();
        $exitCode = $command->execute([$name], $container);
        ob_end_clean();

        return $exitCode;
    }

    private function routesFixture(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Slim\App;
use App\Controller\HomeController;
use App\Controller\HealthController;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index']);

    // @scaffold:routes
};

PHP;
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($path);
    }
}
