<?php

declare(strict_types=1);

namespace App\Debug\Tracy;

use App\Util\SessionInterface;

class SessionPanel extends ExtensionBase implements \Tracy\IBarPanel
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function getTab(): string
    {
        return $this->loadTemplate('session-tab.svg.html', []);
    }

    public function getPanel(): string
    {
        $data = $this->session->all();

        if (empty($data)) {
            return '<h1>Session Data</h1><p>No session data.</p>';
        }

        ksort($data, SORT_NATURAL);
        $table = $this->renderTable($data);

        return <<<HTML
<h1>Session Data</h1>
<div class="tracy-inner" style="max-height:400px">
    {$table}
</div>
HTML;
    }
}
