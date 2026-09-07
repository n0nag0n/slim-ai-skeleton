<?php

declare(strict_types=1);

namespace App\Util;

class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data = [];
    private string $id = '';

    public function start(): void
    {
    }

    public function save(): void
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    public function getId(): string
    {
        if ($this->id === '') {
            $this->id = bin2hex(random_bytes(16));
        }
        return $this->id;
    }

    public function regenerate(): void
    {
        $this->id = bin2hex(random_bytes(16));
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->id = '';
    }
}
