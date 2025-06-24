<?php

namespace Tests\Unit\Services\Commit;

use App\Contracts\CommitSaveInterface;
use App\DataTransferObjects\CommitDTO;
use App\Services\Commit\BufferedCommitSave;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BufferedCommitSaveTest extends TestCase
{
    private CommitSaveInterface&MockObject $mockRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepo = $this->createMock(CommitSaveInterface::class);
    }

    public function testBufferDoesNotFlushBeforeLimit(): void
    {
        $dto = $this->createFakeDto();

        // we expect flush to be called in __destruct
        $this->mockRepo
            ->expects($this->once())
            ->method('saveMany');

        $bufferedSaver = new BufferedCommitSave($this->mockRepo, 2);

        $bufferedSaver($dto);
    }

    public function testBufferFlushesAtLimit(): void
    {
        $dto = $this->createFakeDto();
        $bufferedSaver = new BufferedCommitSave($this->mockRepo, 2);

        $this->mockRepo
            ->expects($this->once())
            ->method('saveMany')
            ->with([$dto->toArray(), $dto->toArray()]);

        $bufferedSaver($dto); // buffer size = 1
        $bufferedSaver($dto); // buffer size = 2 → triggers flush
    }

    public function testBufferFlushesOnDestruct(): void
    {
        $dto = $this->createFakeDto();

        $this->mockRepo
            ->expects($this->once())
            ->method('saveMany')
            ->with([$dto->toArray()]);

        $bufferedSaver = new BufferedCommitSave($this->mockRepo, 3);
        $bufferedSaver($dto); // Only 1 added, not enough to trigger flush

        // Force destruct
        unset($bufferedSaver);
        gc_collect_cycles(); // trigger destructor reliably
    }

    private function createFakeDto(): CommitDTO
    {
        $now = new DateTimeImmutable('2023-01-01T12:00:00Z');

        return new CommitDTO(
            provider: 'github',
            owner: 'nirvana',
            repo: 'nevermind',
            hash: sha1('teen-spirit'),
            author: 'Kurt Cobain',
            authorAvatarUrl: 'https://example.com/avatar.jpg',
            authorHtmlUrl: 'https://github.com/kurt',
            commitDate: $now,
            commitMessage: 'Smells Like Teen Spirit',
            commitHtmlUrl: 'https://github.com/nirvana/nevermind/commit/teen-spirit',
            createdAt: $now,
            updatedAt: $now
        );
    }
}
