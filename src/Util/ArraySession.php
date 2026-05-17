<?php

declare(strict_types=1);

namespace App\Util;

class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data = [];
    /** @phpstan-ignore property.onlyWritten */
    private bool $started = false;
    /** @phpstan-ignore property.onlyWritten */
    private bool $modified = false;
    private string $id = '';

    public function start(): void
    {
        $this->started = true;
    }

    public function save(): void
    {
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

    /** @return array<string, mixed> */
    public function all(): array
    {
        $this->start();
        return $this->data;
    }

    public function getId(): string
    {
        $this->start();
        if ($this->id === '') {
            $this->id = bin2hex(random_bytes(16));
        }
        return $this->id;
    }

    public function regenerate(): void
    {
        $this->start();
        $this->id = bin2hex(random_bytes(16));
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->started = false;
        $this->modified = false;
        $this->id = '';
    }
}
