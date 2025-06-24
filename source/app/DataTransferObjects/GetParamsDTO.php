<?php
namespace App\DataTransferObjects;

use InvalidArgumentException;

final class GetParamsDTO
{
    private const int MAX_COMMIT_COUNT = 1000;

    public function __construct(public int $commitCount = self::MAX_COMMIT_COUNT)
    {
        if ($this->commitCount < 1 || $this->commitCount > self::MAX_COMMIT_COUNT) {
            throw new InvalidArgumentException(
                sprintf('Commit count must be between 1 and %d.', self::MAX_COMMIT_COUNT)
            );
        }
    }
}
