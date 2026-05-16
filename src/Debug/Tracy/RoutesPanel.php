<?php

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

        return <<<HTML
<span title="Routes">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="purple" viewBox="0 0 16 16">
        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3z"/>
    </svg>
    <span class="tracy-label">{$count} routes</span>
</span>
HTML;
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
