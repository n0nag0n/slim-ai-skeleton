<?php

declare(strict_types=1);

namespace App\Util;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public function __construct(private Session $session)
    {
    }

    public function generate(): string
    {
        $token = $this->session->get(self::TOKEN_KEY);

        if ($token === null) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::TOKEN_KEY, $token);
        }

        return $token;
    }

    public function validate(string $token): bool
    {
        $stored = $this->session->get(self::TOKEN_KEY);

        if ($stored === null) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public function regenerate(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set(self::TOKEN_KEY, $token);
    }
}
