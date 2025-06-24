<?php
declare(strict_types=1);

namespace App\DataTransferObjects;

use InvalidArgumentException;

final class PaginationDTO
{
    public function __construct(
        public int $page = 1,
        public int $resultsPerPage = 100,
    ) {
        if ($this->page < 1) {
            throw new InvalidArgumentException('Page number must be >= 1.');
        }
        if ($this->resultsPerPage < 1 || $this->resultsPerPage > 100) {
            throw new InvalidArgumentException('Results per page must be between 1 and 100.');
        }
    }
}
