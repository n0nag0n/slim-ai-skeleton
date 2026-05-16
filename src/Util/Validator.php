<?php

declare(strict_types=1);

namespace App\Util;

class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $labels;

    /** @param array<string, mixed> $data
     *  @param array<string, string> $labels */
    public function __construct(array $data, array $labels = [])
    {
        $this->data = $data;
        $this->labels = $labels;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? str_replace('_', ' ', ucfirst($field));
    }

    public function required(string ...$fields): static
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value === '' || (is_array($value) && empty($value))) {
                $this->errors[$field][] = $this->label($field) . ' is required.';
            }
        }
        return $this;
    }

    public function email(string $field): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $this->label($field) . ' must be a valid email address.';
        }
        return $this;
    }

    public function minLength(string $field, int $min): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && mb_strlen((string) $value) < $min) {
            $this->errors[$field][] = $this->label($field) . ' must be at least ' . $min . ' characters.';
        }
        return $this;
    }

    public function maxLength(string $field, int $max): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && mb_strlen((string) $value) > $max) {
            $this->errors[$field][] = $this->label($field) . ' must not exceed ' . $max . ' characters.';
        }
        return $this;
    }

    public function matches(string $field, string $otherField): static
    {
        $value = $this->data[$field] ?? '';
        $other = $this->data[$otherField] ?? '';
        if ($value !== '' && $value !== $other) {
            $this->errors[$field][] = $this->label($field) . ' must match ' . $this->label($otherField) . '.';
        }
        return $this;
    }

    public function numeric(string $field): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = $this->label($field) . ' must be a number.';
        }
        return $this;
    }

    /** @param array<int, string> $allowed */
    public function inArray(string $field, array $allowed): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            $this->errors[$field][] = $this->label($field) . ' must be one of: ' . $allowedStr . '.';
        }
        return $this;
    }

    public function url(string $field): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $this->label($field) . ' must be a valid URL.';
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, array<int, string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        $first = reset($this->errors);
        return $first ? $first[0] : null;
    }
}
