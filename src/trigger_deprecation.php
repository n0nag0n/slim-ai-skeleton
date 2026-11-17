<?php

declare(strict_types=1);

// Replaces symfony/deprecation-contracts. Twig calls this function.

if (!function_exists('trigger_deprecation')) {
    /**
     * @param mixed ...$args
     */
    function trigger_deprecation(string $package, string $version, string $message, mixed ...$args): void
    {
        $prefix = ($package || $version) ? "Since {$package} {$version}: " : '';
        $text = $args !== [] ? vsprintf($message, $args) : $message;

        @trigger_error($prefix . $text, E_USER_DEPRECATED);
    }
}
