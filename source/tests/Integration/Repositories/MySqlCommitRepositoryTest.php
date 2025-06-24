<?php

namespace Tests\Integration\Repositories;

use App\Exceptions\CommitRepositoryException;
use App\Models\Commit;
use App\Repositories\CommitRepository;
use Carbon\Carbon;
use Tests\TestCase;

class MySqlCommitRepositoryTest extends TestCase
{
    private CommitRepository $repository;
    private Commit $commit;

    protected function setUp(): void
    {
        parent::setUp();

        $now = Carbon::now();

        $this->commit = new Commit();
        $this->repository = new CommitRepository($this->commit);

        $this->commit->newQuery()->create([
            'provider' => 'github',
            'owner' => 'nirvana',
            'repo' => 'nevermind',
            'hash' => 'abc123',
            'author' => 'Kurt Cobain',
            'commit_date' => $now,
            'commit_message' => 'Smells Like Teen Spirit',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function testGetByProviderGroupedByAuthor(): void
    {
        $results = $this->repository->getByProviderGroupedByAuthor(1, 10, 'github');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('Kurt Cobain', $results);
    }

    public function testGetByProviderGroupedByAuthorThrowsExceptionForInvalidPage(): void
    {
        $this->expectException(CommitRepositoryException::class);
        $this->expectExceptionMessage('Offset and limit must be greater than 0.');

        $this->repository->getByProviderGroupedByAuthor(0, 10, 'github');
    }

    public function testCountByProvider(): void
    {
        $now = Carbon::now();

        // Add a second commit with different owner/repo
        Commit::create([
            'provider' => 'github',
            'owner' => 'soundgarden',
            'repo' => 'badmotorfinger',
            'hash' => 'def456',
            'author' => 'Chris Cornell',
            'commit_date' => $now,
            'commit_message' => 'Outshined',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Total for provider
        $this->assertSame(2, $this->repository->countByProvider('github'));

        // With owner filter
        $this->assertSame(1, $this->repository->countByProvider('github', 'soundgarden'));

        // With owner + repo filter
        $this->assertSame(1, $this->repository->countByProvider('github', 'soundgarden', 'badmotorfinger'));

        // Non-matching repo
        $this->assertSame(0, $this->repository->countByProvider('github', 'soundgarden', 'superunknown'));
    }

    public function testSaveMany(): void
    {
        $now = Carbon::now();

        $this->repository->saveMany([
            [
                'provider' => 'github',
                'owner' => 'pearljam',
                'repo' => 'ten',
                'hash' => 'xyz789',
                'author' => 'Eddie Vedder',
                'author_avatar_url' => null,
                'author_html_url' => null,
                'commit_date' => $now->toDateTimeString(),
                'commit_message' => 'Alive',
                'commit_html_url' => null,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]
        ]);

        $exists = $this->commit->newQuery()
            ->where('hash', 'xyz789')
            ->where('author', 'Eddie Vedder')
            ->exists();

        $this->assertTrue($exists, 'Expected commit was not found in the database.');
    }

    public function testSaveManyThrowsCommitRepositoryException(): void
    {
        $this->expectException(CommitRepositoryException::class);

        $this->repository->saveMany([
            ['invalid_field' => 'nope']
        ]);
    }
}
