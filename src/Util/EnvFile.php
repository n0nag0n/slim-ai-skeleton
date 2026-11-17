<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Loads KEY=value lines from a .env file into $_ENV and $_SERVER.
 *
 * Existing variables are left alone. A missing file is ignored. Quoted
 * values, blank values, and end-of-line comments are accepted. ${VAR},
 * multiline values, and the export keyword are rejected.
 */
class EnvFile
{
    public static function load(string $directory, string $name = '.env'): void
    {
        $path = $directory . '/' . $name;
        if (!is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read ' . $path);
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        $lineNumber = 0;
        foreach (preg_split("/\n/", $contents) ?: [] as $line) {
            $lineNumber++;
            self::applyLine(rtrim($line, "\r"), $lineNumber, $path);
        }
    }

    private static function applyLine(string $line, int $lineNumber, string $path): void
    {
        $trimmed = ltrim($line, " \t");
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            return;
        }

        if (preg_match('/^export(?:\s|$)/', $trimmed) === 1) {
            throw new \RuntimeException("The export keyword is not supported in {$path} on line {$lineNumber}");
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            throw new \RuntimeException("Expected KEY=value in {$path} on line {$lineNumber}");
        }

        $name = trim($parts[0]);
        if (preg_match('/^[A-Za-z_.][A-Za-z0-9_.]*$/', $name) !== 1) {
            throw new \RuntimeException("Invalid variable name in {$path} on line {$lineNumber}");
        }

        if (array_key_exists($name, $_ENV) || array_key_exists($name, $_SERVER)) {
            return;
        }

        $value = self::parseValue(trim($parts[1]), $lineNumber, $path);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    private static function parseValue(string $value, int $lineNumber, string $path): string
    {
        if ($value === '' || str_starts_with($value, '#')) {
            return '';
        }

        $quote = $value[0];
        if ($quote === '"' || $quote === "'") {
            return self::parseQuoted($value, $quote, $lineNumber, $path);
        }

        if (preg_match('/\s#/', $value, $match, PREG_OFFSET_CAPTURE) === 1) {
            $value = rtrim(substr($value, 0, $match[0][1]));
        }

        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s$\\\\\'"]/', $value) === 1) {
            throw new \RuntimeException(
                "Unsupported value in {$path} on line {$lineNumber}. "
                . 'Quote values that contain spaces. Variable expansion is not supported.'
            );
        }

        return $value;
    }

    private static function parseQuoted(string $value, string $quote, int $lineNumber, string $path): string
    {
        $length = strlen($value);
        $parsed = '';

        for ($i = 1; $i < $length; $i++) {
            $char = $value[$i];

            if ($quote === '"' && $char === '\\') {
                $next = $value[$i + 1] ?? '';
                $parsed .= match ($next) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    '"', '\\' => $next,
                    default => throw new \RuntimeException(
                        "Unsupported escape sequence in {$path} on line {$lineNumber}"
                    ),
                };
                $i++;
                continue;
            }

            if ($quote === '"' && $char === '$') {
                throw new \RuntimeException("Variable expansion is not supported in {$path} on line {$lineNumber}");
            }

            if ($char === $quote) {
                self::assertTrailing(substr($value, $i + 1), $lineNumber, $path);
                return $parsed;
            }

            $parsed .= $char;
        }

        throw new \RuntimeException("Unclosed quote in {$path} on line {$lineNumber}");
    }

    private static function assertTrailing(string $rest, int $lineNumber, string $path): void
    {
        $rest = ltrim($rest, " \t");
        if ($rest === '' || str_starts_with($rest, '#')) {
            return;
        }

        throw new \RuntimeException("Unexpected characters after the value in {$path} on line {$lineNumber}");
    }
}
