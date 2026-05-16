<?php

namespace App\Debug\Tracy;

abstract class ExtensionBase
{
    protected int $valueWidth = 300;

    public function setValueWidth(int $valueWidth): void
    {
        $this->valueWidth = $valueWidth;
    }

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
            $value = $this->ellipsis($value, 60)
                . ' <a href="#' . $id . '" class="tracy-toggle tracy-collapsed">more</a>'
                . '<pre id="' . $id . '" class="tracy-collapsed"'
                . ' style="max-width: ' . $this->valueWidth . 'px; overflow: auto; min-height: 40px; background-color: #EEE; padding: 5px;">'
                . '<code>' . $value . '</code></pre>';
        }

        return $value;
    }

    protected function ellipsis(string $text, int $limit = 30): string
    {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
    }

    protected function renderTable(array $data): string
    {
        $rows = '';
        foreach ($data as $key => $value) {
            $rows .= '<tr><td>' . htmlspecialchars((string) $key) . '</td><td>' . $this->handleLongStrings($value) . '</td></tr>';
        }
        return '<table><tbody>' . $rows . '</tbody></table>';
    }
}
