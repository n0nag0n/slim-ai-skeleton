<?php

namespace App\Test\Util;

use App\Util\Pagination;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $p = new Pagination(0);
        $this->assertSame(0, $p->getTotalItems());
        $this->assertSame(1, $p->getCurrentPage());
        $this->assertSame(20, $p->getPerPage());
        $this->assertSame(0, $p->getOffset());
        $this->assertSame(20, $p->getLimit());
        $this->assertSame(0, $p->getTotalPages());
        $this->assertFalse($p->hasPrevious());
        $this->assertFalse($p->hasNext());
        $this->assertNull($p->getPreviousPage());
        $this->assertNull($p->getNextPage());
    }

    public function testFirstPageWithItems(): void
    {
        $p = new Pagination(50, 1, 10);
        $this->assertSame(0, $p->getOffset());
        $this->assertSame(5, $p->getTotalPages());
        $this->assertFalse($p->hasPrevious());
        $this->assertTrue($p->hasNext());
        $this->assertNull($p->getPreviousPage());
        $this->assertSame(2, $p->getNextPage());
    }

    public function testMiddlePage(): void
    {
        $p = new Pagination(50, 3, 10);
        $this->assertSame(20, $p->getOffset());
        $this->assertTrue($p->hasPrevious());
        $this->assertTrue($p->hasNext());
        $this->assertSame(2, $p->getPreviousPage());
        $this->assertSame(4, $p->getNextPage());
    }

    public function testLastPage(): void
    {
        $p = new Pagination(50, 5, 10);
        $this->assertSame(40, $p->getOffset());
        $this->assertTrue($p->hasPrevious());
        $this->assertFalse($p->hasNext());
        $this->assertSame(4, $p->getPreviousPage());
        $this->assertNull($p->getNextPage());
    }

    public function testClampsCurrentPageToMinimum(): void
    {
        $p = new Pagination(50, 0, 10);
        $this->assertSame(1, $p->getCurrentPage());
    }

    public function testClampsPerPageToMinimum(): void
    {
        $p = new Pagination(50, 1, 0);
        $this->assertSame(1, $p->getPerPage());
    }

    public function testClampsPerPageToMaximum(): void
    {
        $p = new Pagination(50, 1, 200);
        $this->assertSame(100, $p->getPerPage());
    }

    public function testClampsTotalItemsToMinimum(): void
    {
        $p = new Pagination(-10);
        $this->assertSame(0, $p->getTotalItems());
    }

    public function testExactPageBoundary(): void
    {
        $p = new Pagination(30, 3, 10);
        $this->assertSame(3, $p->getTotalPages());
        $this->assertFalse($p->hasNext());
    }

    public function testToArray(): void
    {
        $p = new Pagination(25, 2, 10);
        $this->assertSame([
            'page' => 2,
            'per_page' => 10,
            'total_items' => 25,
            'total_pages' => 3,
            'has_previous' => true,
            'has_next' => true,
        ], $p->toArray());
    }
}
