<?php

namespace App\Debug\Tracy;

use App\Debug\DbalQueryLogger;

class DatabasePanel extends ExtensionBase implements \Tracy\IBarPanel
{
    private const LONG_QUERY_TIME = 0.5;

    public function getTab(): string
    {
        $queries = DbalQueryLogger::getQueries()->getAll();
        $count = count($queries);
        $totalTime = 0;
        $longCount = 0;

        foreach ($queries as $q) {
            $totalTime += $q['time'];
            if ($q['time'] > self::LONG_QUERY_TIME) {
                $longCount++;
            }
        }

        $totalTime = round($totalTime, 4);
        $warning = $longCount > 0
            ? '<span style="color:red;font-weight:bold"> ' . $longCount . ' slow!</span>'
            : '';

        return <<<HTML
<span title="Database Queries">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="darkTurquoise" viewBox="0 0 16 16">
        <path d="M1.333 2.667C1.333 1.194 4.318 0 8 0s6.667 1.194 6.667 2.667V4c0 1.473-2.985 2.667-6.667 2.667S1.333 5.473 1.333 4V2.667z"/>
        <path d="M1.333 6.334v3C1.333 10.805 4.318 12 8 12s6.667-1.194 6.667-2.667V6.334a6.51 6.51 0 0 1-1.458.79C11.81 7.684 9.967 8 8 8c-1.966 0-3.809-.317-5.208-.876a6.508 6.508 0 0 1-1.458-.79z"/>
        <path d="M14.667 11.668a6.51 6.51 0 0 1-1.458.789c-1.4.56-3.242.876-5.21.876-1.966 0-3.809-.316-5.208-.876a6.51 6.51 0 0 1-1.458-.79v1.666C1.333 14.806 4.318 16 8 16s6.667-1.194 6.667-2.667v-1.665z"/>
    </svg>
    <span class="tracy-label">{$totalTime}s / {$count}{$warning}</span>
</span>
HTML;
    }

    public function getPanel(): string
    {
        $queries = DbalQueryLogger::getQueries()->getAll();

        if (empty($queries)) {
            return '<h1>Database Queries</h1><p>No queries executed.</p>';
        }

        $rows = '';
        foreach ($queries as $i => $q) {
            $sql = $this->handleLongStrings($q['sql']);
            $params = !empty($q['params']) ? $this->handleLongStrings($q['params']) : '—';
            $time = round($q['time'], 4);
            $source = $this->handleLongStrings($q['source'] ?? '');
            $slow = $q['time'] > self::LONG_QUERY_TIME ? ' style="background:coral"' : '';
            $rows .= <<<HTML
<tr{$slow}>
    <td>{$time}s</td>
    <td>{$sql}</td>
    <td>{$params}</td>
    <td>{$source}</td>
    <td>{$q['rows']}</td>
</tr>
HTML;
        }

        return <<<HTML
<h1>Database Queries</h1>
<div class="tracy-inner" style="max-height:400px;overflow:auto">
    <table class="tracy-sortable">
        <thead>
            <tr>
                <th>Time</th>
                <th>SQL</th>
                <th>Params</th>
                <th>Source</th>
                <th>Rows</th>
            </tr>
        </thead>
        <tbody>{$rows}</tbody>
    </table>
</div>
HTML;
    }
}
