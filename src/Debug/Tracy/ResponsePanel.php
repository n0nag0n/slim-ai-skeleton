<?php

declare(strict_types=1);

namespace App\Debug\Tracy;

use App\Debug\TracyMiddleware;

class ResponsePanel extends ExtensionBase implements \Tracy\IBarPanel
{
    public function getTab(): string
    {
        $code = TracyMiddleware::$responseData['status_code'] ?? '—';

        return <<<HTML
<span title="Response">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="orange" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M15.528 2.973a.75.75 0 0 1 .472.696v8.662a.75.75 0 0 1-.472.696l-7.25 2.9a.75.75 0 0 1-.557 0l-7.25-2.9A.75.75 0 0 1 0 12.331V3.669a.75.75 0 0 1 .471-.696L7.443.184l.01-.003.268-.108a.75.75 0 0 1 .558 0l.269.108.01.003zM10.404 2 4.25 4.461 1.846 3.5 1 3.839v.4l6.5 2.6v7.922l.5.2.5-.2V6.84l6.5-2.6v-.4l-.846-.339L8 5.961 5.596 5l6.154-2.461z"/>
    </svg>
    <span class="tracy-label">{$code}</span>
</span>
HTML;
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

        return <<<HTML
<h1>Response</h1>
<div class="tracy-inner" style="max-height:500px;overflow:auto">
    {$commonHtml}
    {$headerHtml}
    <table>
        <thead><tr><th colspan="2" style="background:#EEE">Body</th></tr></thead>
        <tbody>
            <tr><td colspan="2"><pre style="max-height:300px;overflow:auto;background:#EEE;padding:5px;margin:0"><code>{$escapedBody}</code></pre></td></tr>
        </tbody>
    </table>
</div>
HTML;
    }

    private function renderTableSection(string $title, array $data): string
    {
        $rows = '';
        foreach ($data as $key => $value) {
            $rows .= '<tr><td>' . htmlspecialchars((string) $key) . '</td><td>' . $this->handleLongStrings($value) . '</td></tr>';
        }
        return <<<HTML
<table>
    <thead><tr><th colspan="2" style="background:#EEE">{$title}</th></tr></thead>
    <tbody>{$rows}</tbody>
</table>
HTML;
    }
}
