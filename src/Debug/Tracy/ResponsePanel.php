<?php

declare(strict_types=1);

namespace App\Debug\Tracy;

use App\Debug\TracyMiddleware;

class ResponsePanel extends ExtensionBase implements \Tracy\IBarPanel
{
    public function getTab(): string
    {
        $code = TracyMiddleware::$responseData['status_code'] ?? '—';

        return $this->loadTemplate('response-tab.svg.html', ['code' => $code]);
    }

    public function getPanel(): string
    {
        $data = TracyMiddleware::$responseData;

        if (empty($data)) {
            return '<h1>Response</h1><p>No response data captured.</p>';
        }

        $common = [
            'status_code' => $data['status_code'],
            'reason_phrase' => $data['reason_phrase'],
            'protocol' => $data['protocol_version'],
        ];

        $commonHtml = $this->renderTableSection('Status', $common);

        $headerHtml = '';
        $headers = $data['headers'] ?? [];
        if (!empty($headers)) {
            $headerHtml = $this->renderTableSection('Headers', $headers);
        }

        $bodyPreview = $data['body'] ?? '';
        $escapedBody = htmlspecialchars($bodyPreview);

        $bodyRow = '<tr><td colspan="2"><pre style="max-height:300px;overflow:auto';
        $bodyRow .= ';background:#EEE;padding:5px;margin:0"><code>' . $escapedBody;
        $bodyRow .= '</code></pre></td></tr>';

        return <<<HTML
<h1>Response</h1>
<div class="tracy-inner" style="max-height:500px;overflow:auto">
    {$commonHtml}
    {$headerHtml}
    <table>
        <thead><tr><th colspan="2" style="background:#EEE">Body</th></tr></thead>
        <tbody>
            {$bodyRow}
        </tbody>
    </table>
</div>
HTML;
    }

    private function renderTableSection(string $title, array $data): string
    {
        $rows = '';
        foreach ($data as $key => $value) {
            $keyHtml = htmlspecialchars((string) $key);
            $valHtml = $this->handleLongStrings($value);
            $rows .= "<tr><td>{$keyHtml}</td><td>{$valHtml}</td></tr>";
        }
        return <<<HTML
<table>
    <thead><tr><th colspan="2" style="background:#EEE">{$title}</th></tr></thead>
    <tbody>{$rows}</tbody>
</table>
HTML;
    }
}
