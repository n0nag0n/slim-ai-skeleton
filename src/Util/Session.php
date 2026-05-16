<?php

namespace App\Util;

class Session
{
    private array $data = [];
    private bool $started = false;
    private bool $modified = false;

    public function start(): void
    {
        if ($this->started) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->data = $_SESSION;
            $this->started = true;
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $this->data = $_SESSION;
            session_write_close();
        }
        $this->started = true;
    }

    public function save(): void
    {
        if (!$this->modified) {
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $_SESSION = $this->data;
            session_write_close();
        }
        $this->modified = false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $this->data[$key] = $value;
        $this->modified = true;
    }

    public function delete(string $key): void
    {
        $this->start();
        unset($this->data[$key]);
        $this->modified = true;
    }

    public function has(string $key): bool
    {
        $this->start();
        return isset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->start();
        $this->data = [];
        $this->modified = true;
    }

    public function all(): array
    {
        $this->start();
        return $this->data;
    }

    public function getId(): string
    {
        $this->start();
        return session_id();
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        session_write_close();
        $this->started = true;
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        } elseif (session_status() === PHP_SESSION_NONE) {
            session_start();
            $_SESSION = [];
            session_destroy();
        }
        $this->data = [];
        $this->started = false;
        $this->modified = false;
    }
}
