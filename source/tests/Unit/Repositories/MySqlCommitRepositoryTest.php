<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\CommitRepositoryException;
use App\Models\Commit;
use App\Repositories\MySqlCommitRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\BuilderFake;

final class MySqlCommitRepositoryTest extends TestCase
{
    private Commit&MockObject $commitMock;
    private MySqlCommitRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commitMock = $this->createMock(Commit::class);
        $this->repository = new MySqlCommitRepository($this->commitMock);
    }

    #[Test]
    public function testSaveManyInsertsChunks(): void
    {
        $commits = array_fill(0, 600, ['hash' => 'abc123']);

        $builderFake = new class() extends BuilderFake {
            public int $insertCalls = 0;

            public function insertOrIgnore(array $values): true
            {
                $this->insertCalls++;
                return true;
            }
        };

        $this->commitMock->method('newQuery')->willReturn($builderFake);

        $this->repository->saveMany($commits);

        self::assertSame(2, $builderFake->insertCalls, 'Expected insertOrIgnore to be called twice for chunks of 500');
    }

    #[Test]
    public function testGetByProviderGroupedByAuthorWithFilters(): void
    {
        $offset = 2;
        $limit = 10;
        $provider = 'github';
        $owner = 'octocat';
        $repo = 'hello-world';

        $builderFake = new class() extends BuilderFake {
            public function where($column, $operator = null, $value = null, $boolean = 'and'): BuilderFake|static
            {
                return $this;
            }

            public function orderBy($column, $direction = 'asc'): BuilderFake|static
            {
                return $this;
            }

            public function skip($value): BuilderFake|static
            {
                return $this;
            }

            public function take($value): BuilderFake|static
            {
                return $this;
            }

            public function get($columns = ['*']): \Illuminate\Database\Eloquent\Collection|Collection
            {
                return collect([
                    ['author' => 'alice'],
                    ['author' => 'bob'],
                    ['author' => 'alice'],
                ]);
            }
        };

        $this->commitMock->method('newQuery')->willReturn($builderFake);

        $result = $this->repository->getByProviderGroupedByAuthor($offset, $limit, $provider, $owner, $repo);

        $expected = [
            'alice' => [
                ['author' => 'alice'],
                ['author' => 'alice'],
            ],
            'bob' => [
                ['author' => 'bob'],
            ],
        ];

        self::assertSame($expected, $result);
    }

    #[Test]
    public function testCountByProviderWithFilters(): void
    {
        $provider = 'gitlab';
        $owner = 'owner';
        $repo = 'repo';

        $builderFake = new class() extends BuilderFake {
            public function where($column, $operator = null, $value = null, $boolean = 'and'): BuilderFake|static
            {
                return $this;
            }

            public function count($columns = '*'): int
            {
                return 42;
            }
        };

        $this->commitMock->method('newQuery')->willReturn($builderFake);

        $count = $this->repository->countByProvider($provider, $owner, $repo);

        self::assertSame(42, $count);
    }

    #[Test]
    public function testGetByProviderGroupedByAuthorThrowsOnInvalidPagination(): void
    {
        $this->expectException(CommitRepositoryException::class);
        $this->expectExceptionMessage('Offset and limit must be greater than 0.');

        $this->repository->getByProviderGroupedByAuthor(0, 0, 'github');
    }
}
