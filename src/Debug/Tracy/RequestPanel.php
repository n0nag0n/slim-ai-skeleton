<?php

declare(strict_types=1);

namespace App\Debug\Tracy;

use App\Debug\TracyMiddleware;

class RequestPanel extends ExtensionBase implements \Tracy\IBarPanel
{
    public function getTab(): string
    {
        $method = TracyMiddleware::$requestData['method'] ?? 'GET';
        $uri = TracyMiddleware::$requestData['uri'] ?? '';

        return <<<HTML
<span title="Request">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="green" viewBox="0 0 16 16">
        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm5.5 10a.5.5 0 0 0 .832.374l4.5-4a.5.5 0 0 0 0-.748l-4.5-4A.5.5 0 0 0 5.5 4v8z"/>
    </svg>
    <span class="tracy-label">{$method} {$this->ellipsis($uri, 40)}</span>
</span>
HTML;
    }

    public function getPanel(): string
    {
        $data = TracyMiddleware::$requestData;

        if (empty($data)) {
            return '<h1>Request</h1><p>No request data captured.</p>';
        }

        $common = [
            'method' => $data['method'],
            'uri' => $data['uri'],
            'scheme' => $data['scheme'],
            'host' => $data['host'],
            'port' => $data['port'],
            'path' => $data['path'],
            'query_string' => $data['query_string'],
            'protocol' => $data['protocol_version'],
            'content_type' => $data['content_type'],
            'content_length' => $data['content_length'],
        ];

        $commonHtml = $this->renderTableSection('Common', $common);
        $headersHtml = $this->renderTableSection('Headers', $data['headers'] ?? []);
        $paramsHtml = $this->renderTableSection('Query Parameters', $data['query_params'] ?? []);
        $bodyHtml = $this->renderTableSection('Parsed Body', $data['parsed_body'] ?? []);
        $cookiesHtml = $this->renderTableSection('Cookies', $data['cookies'] ?? []);
        $filesHtml = $this->renderTableSection('Uploaded Files', $data['uploaded_files'] ?? []);
        $serverHtml = $this->renderTableSection('Server Params', $data['server_params'] ?? []);
        $attrHtml = $this->renderTableSection('Attributes', $data['attributes'] ?? []);

        return <<<HTML
<h1>Request</h1>
<div class="tracy-inner" style="max-height:500px;overflow:auto">
    {$commonHtml}
    {$headersHtml}
    {$paramsHtml}
    {$bodyHtml}
    {$cookiesHtml}
    {$filesHtml}
    {$serverHtml}
    {$attrHtml}
</div>
HTML;
    }

    private function renderTableSection(string $title, array $data): string
    {
        if (empty($data)) {
            return '';
        }
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
