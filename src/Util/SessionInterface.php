<?php

declare(strict_types=1);

namespace App\Util;

interface SessionInterface
{
    public function start(): void;
    public function save(): void;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function delete(string $key): void;
    public function has(string $key): bool;
    public function clear(): void;
    /** @return array<string, mixed> */
    public function all(): array;
    public function getId(): string;
    public function regenerate(): void;
    public function destroy(): void;
}
