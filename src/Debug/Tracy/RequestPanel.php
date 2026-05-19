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

        return $this->loadTemplate('request-tab.svg.html', [
            'method' => $method,
            'uri' => $this->ellipsis($uri, 40),
        ]);
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
