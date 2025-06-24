<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\GetParamsDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GetParamsDTOTest extends TestCase
{
    public function testConstructorAcceptsValidCommitCount(): void
    {
        $dto = new GetParamsDTO(100);
        $this->assertSame(100, $dto->commitCount);
    }

    public function testConstructorDefaultsToMaxWhenNoValueProvided(): void
    {
        $dto = new GetParamsDTO();
        $this->assertSame(1000, $dto->commitCount);
    }

    public function testConstructorThrowsForCommitCountLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commit count must be between 1 and 1000.');
        new GetParamsDTO(0);
    }

    public function testConstructorThrowsForCommitCountGreaterThanMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commit count must be between 1 and 1000.');
        new GetParamsDTO(1001);
    }
}
