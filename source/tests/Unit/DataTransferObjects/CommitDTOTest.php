<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\CommitDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CommitDTOTest extends TestCase
{
    public function testToArrayReturnsCorrectData(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');

        $dto = new CommitDTO(
            provider: 'github',
            owner: 'soundgarden',
            repo: 'superunknown',
            hash: 'abc123',
            author: 'Chris Cornell',
            authorAvatarUrl: 'https://avatars.githubusercontent.com/u/1?v=4',
            authorHtmlUrl: 'https://github.com/chriscornell',
            commitDate: $now,
            commitMessage: 'Added Black Hole Sun',
            commitHtmlUrl: 'https://github.com/soundgarden/superunknown/commit/abc123',
            createdAt: $now,
            updatedAt: $now,
        );

        $expected = [
            'provider' => 'github',
            'owner' => 'soundgarden',
            'repo' => 'superunknown',
            'hash' => 'abc123',
            'author' => 'Chris Cornell',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/1?v=4',
            'author_html_url' => 'https://github.com/chriscornell',
            'commit_date' => '2024-01-01 12:00:00',
            'commit_message' => 'Added Black Hole Sun',
            'commit_html_url' => 'https://github.com/soundgarden/superunknown/commit/abc123',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
        ];

        $this->assertSame($expected, $dto->toArray());
    }
}
