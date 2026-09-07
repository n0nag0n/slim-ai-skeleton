<?php

declare(strict_types=1);

namespace App\Debug\Tracy;

abstract class ExtensionBase
{
    protected function handleLongStrings(mixed $value): string
    {
        if (is_object($value)) {
            $value = '[' . $value::class . ']';
        } elseif (is_array($value)) {
            $value = print_r($value, true);
        }

        $value = is_bool($value) || is_int($value) ? var_export($value, true) : htmlspecialchars((string) $value);

        if (str_contains($value, "\n")) {
            $lines = explode("\n", $value);
            $value = '';
            foreach ($lines as $line) {
                $value .= trim($line) . "\n";
            }
        }

        if (strlen($value) > 60) {
            $id = uniqid('tracy-panel-');
            $style = 'max-width: 300px; overflow: auto;';
            $style .= ' min-height: 40px; background-color: #EEE; padding: 5px;';
            $value = $this->ellipsis($value, 60)
                . ' <a href="#' . $id . '" class="tracy-toggle tracy-collapsed">more</a>'
                . '<pre id="' . $id . '" class="tracy-collapsed" style="' . $style . '">'
                . '<code>' . $value . '</code></pre>';
        }

        return $value;
    }

    protected function ellipsis(string $text, int $limit = 30): string
    {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
    }

    protected function loadTemplate(string $name, array $vars): string
    {
        $path = dirname(__DIR__, 3) . '/templates/tracy/' . $name;
        $content = file_get_contents($path);
        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }
        return $content;
    }

    protected function renderTable(array $data): string
    {
        return '<table><tbody>' . $this->tableRows($data) . '</tbody></table>';
    }

    protected function renderTableSection(string $title, array $data): string
    {
        if ($data === []) {
            return '';
        }

        $titleHtml = htmlspecialchars($title);
        $rows = $this->tableRows($data);

        return <<<HTML
<table>
    <thead><tr><th colspan="2" style="background:#EEE">{$titleHtml}</th></tr></thead>
    <tbody>{$rows}</tbody>
</table>
HTML;
    }

    private function tableRows(array $data): string
    {
        $rows = '';
        foreach ($data as $key => $value) {
            $keyHtml = htmlspecialchars((string) $key);
            $valHtml = $this->handleLongStrings($value);
            $rows .= "<tr><td>{$keyHtml}</td><td>{$valHtml}</td></tr>";
        }
        return $rows;
    }
}
