<?php

namespace App\Debug\Tracy;

use App\Util\SessionInterface;

class SessionPanel extends ExtensionBase implements \Tracy\IBarPanel
{
    public function __construct(private SessionInterface $session) {}

    public function getTab(): string
    {
        return <<<HTML
<span title="Session Data">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="tan" viewBox="0 0 16 16">
        <path d="M12.643 15C13.979 15 15 13.845 15 12.5V5H1v7.5C1 13.845 2.021 15 3.357 15h9.286zM5.5 7h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zM.8 1a.8.8 0 0 0-.8.8V3a.8.8 0 0 0 .8.8h14.4A.8.8 0 0 0 16 3V1.8a.8.8 0 0 0-.8-.8H.8z"/>
    </svg>
    <span class="tracy-label">Session</span>
</span>
HTML;
    }

    public function getPanel(): string
    {
        $data = $this->session->all();

        if (empty($data)) {
            return '<h1>Session Data</h1><p>No session data.</p>';
        }

        ksort($data, SORT_NATURAL);

        $rows = '';
        foreach ($data as $key => $value) {
            $rows .= '<tr><td>' . htmlspecialchars((string) $key) . '</td><td>' . $this->handleLongStrings($value) . '</td></tr>';
        }

        return <<<HTML
<h1>Session Data</h1>
<div class="tracy-inner" style="max-height:400px">
    <table>
        <tbody>{$rows}</tbody>
    </table>
</div>
HTML;
    }
}
