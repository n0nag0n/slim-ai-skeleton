<?php

namespace App\Util;

class Pagination
{
    private int $totalItems;
    private int $currentPage;
    private int $perPage;

    public function __construct(int $totalItems, int $currentPage = 1, int $perPage = 20)
    {
        $this->totalItems = max(0, $totalItems);
        $this->currentPage = max(1, $currentPage);
        $this->perPage = max(1, min(100, $perPage));
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function getLimit(): int
    {
        return $this->perPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->totalItems / $this->perPage);
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    public function getPreviousPage(): ?int
    {
        return $this->hasPrevious() ? $this->currentPage - 1 : null;
    }

    public function getNextPage(): ?int
    {
        return $this->hasNext() ? $this->currentPage + 1 : null;
    }

    /** @return array<string, int|bool> */
    public function toArray(): array
    {
        return [
            'page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total_items' => $this->totalItems,
            'total_pages' => $this->getTotalPages(),
            'has_previous' => $this->hasPrevious(),
            'has_next' => $this->hasNext(),
        ];
    }
}
