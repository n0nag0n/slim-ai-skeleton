<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Basic trusted reverse proxy support.
 *
 * When your PHP app sits behind nginx (or any reverse proxy) that terminates TLS
 * or forwards headers, the PHP process sees the proxy's IP as REMOTE_ADDR and
 * usually "http" for the scheme. This breaks:
 *   - Session cookie "secure" flag (Session.php reads $_SERVER['HTTPS'])
 *   - $request->getUri()->getScheme() / host in Slim
 *   - Any code relying on the real client IP or original scheme
 *
 * Usage (typical nginx):
 *   Set in .env:
 *     TRUSTED_PROXIES=127.0.0.1,::1
 *   Or for container/internal networks where you control the proxy:
 *     TRUSTED_PROXIES=*
 *
 * In your nginx config (example):
 *   proxy_set_header X-Forwarded-Proto $scheme;
 *   proxy_set_header X-Forwarded-Host $host;
 *   proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
 *   proxy_set_header X-Forwarded-Port $server_port;
 *
 * Call early (public/index.php does this right after loading .env).
 */
final class TrustedProxy
{
    public static function configure(): void
    {
        $trusted = $_ENV['TRUSTED_PROXIES'] ?? '';
        if ($trusted === '') {
            return;
        }

        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $proxies = array_map('trim', explode(',', $trusted));
        $trustAll = in_array('*', $proxies, true) || in_array('all', $proxies, true);

        if (!$trustAll && $remote !== '' && !in_array($remote, $proxies, true)) {
            return;
        }

        // X-Forwarded-Proto / X-Forwarded-Scheme
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO']
            ?? $_SERVER['HTTP_X_FORWARDED_SCHEME']
            ?? '';

        if ($proto === 'https') {
            $_SERVER['HTTPS'] = 'on';
        } elseif ($proto === 'http') {
            unset($_SERVER['HTTPS']);
            // Some setups also set this
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        }

        // Host (may contain :port)
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        if ($host !== '') {
            $_SERVER['HTTP_HOST'] = $host;
            $hostOnly = explode(':', $host, 2)[0];
            $_SERVER['SERVER_NAME'] = $hostOnly;
        }

        // Port override
        $port = $_SERVER['HTTP_X_FORWARDED_PORT'] ?? '';
        if ($port !== '') {
            $_SERVER['SERVER_PORT'] = $port;
        }

        // Original client IP (left-most in X-Forwarded-For)
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwardedFor !== '') {
            $ips = array_map('trim', explode(',', $forwardedFor));
            $client = $ips[0];
            if ($client !== '') {
                $_SERVER['REMOTE_ADDR'] = $client;
            }
        }
    }
}
