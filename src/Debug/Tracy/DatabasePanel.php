<?php

declare(strict_types=1);

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

        return $this->loadTemplate('database-tab.svg.html', [
            'totalTime' => $totalTime,
            'count' => $count,
            'warning' => $warning,
        ]);
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
