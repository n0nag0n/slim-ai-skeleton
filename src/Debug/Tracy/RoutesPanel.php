<?php

declare(strict_types=1);

namespace App\Debug\Tracy;

use Slim\App;
use Slim\Routing\RouteCollector;

class RoutesPanel extends ExtensionBase implements \Tracy\IBarPanel
{
    private array $routes = [];

    public function __construct(App $app)
    {
        /** @var RouteCollector $collector */
        $collector = $app->getRouteCollector();
        foreach ($collector->getRoutes() as $route) {
            $this->routes[] = [
                'methods' => implode(', ', $route->getMethods()),
                'pattern' => $route->getPattern(),
                'name' => $route->getName() ?? '—',
                'callable' => is_array($route->getCallable())
                    ? implode('::', $route->getCallable())
                    : (string) $route->getCallable(),
            ];
        }
    }

    public function getTab(): string
    {
        $count = count($this->routes);

        return $this->loadTemplate('routes-tab.svg.html', ['count' => $count]);
    }

    public function getPanel(): string
    {
        $rows = '';
        foreach ($this->routes as $route) {
            $rows .= <<<HTML
<tr>
    <td><code>{$route['methods']}</code></td>
    <td><code>{$route['pattern']}</code></td>
    <td>{$route['name']}</td>
    <td>{$route['callable']}</td>
</tr>
HTML;
        }

        return <<<HTML
<h1>Routes</h1>
<div class="tracy-inner" style="max-height:400px;overflow:auto">
    <table class="tracy-sortable">
        <thead>
            <tr>
                <th>Methods</th>
                <th>Pattern</th>
                <th>Name</th>
                <th>Handler</th>
            </tr>
        </thead>
        <tbody>{$rows}</tbody>
    </table>
</div>
HTML;
    }
}
