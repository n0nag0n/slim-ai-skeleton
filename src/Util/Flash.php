<?php

declare(strict_types=1);

namespace App\Util;

class Flash
{
    public function __construct(private Session $session)
    {
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set('_flash_' . $key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $flashKey = '_flash_' . $key;
        $value = $this->session->get($flashKey, $default);
        $this->session->delete($flashKey);
        return $value;
    }

    public function has(string $key): bool
    {
        return $this->session->has('_flash_' . $key);
    }
}
