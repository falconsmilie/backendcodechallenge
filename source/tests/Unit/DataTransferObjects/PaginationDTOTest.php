<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\PaginationDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaginationDTOTest extends TestCase
{
    public function testConstructorUsesDefaultValues(): void
    {
        $dto = new PaginationDTO();
        $this->assertSame(1, $dto->page);
        $this->assertSame(100, $dto->resultsPerPage);
    }

    public function testConstructorAcceptsValidValues(): void
    {
        $dto = new PaginationDTO(page: 2, resultsPerPage: 50);
        $this->assertSame(2, $dto->page);
        $this->assertSame(50, $dto->resultsPerPage);
    }

    public function testConstructorThrowsForPageLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page number must be >= 1.');
        new PaginationDTO(page: 0);
    }

    public function testConstructorThrowsForResultsPerPageLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Results per page must be between 1 and 100.');
        new PaginationDTO(resultsPerPage: 0);
    }

    public function testConstructorThrowsForResultsPerPageGreaterThan100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Results per page must be between 1 and 100.');
        new PaginationDTO(resultsPerPage: 101);
    }
}
