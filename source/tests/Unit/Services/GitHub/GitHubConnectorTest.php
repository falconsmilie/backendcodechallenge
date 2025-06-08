<?php

namespace Tests\Unit\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Repositories\CommitRepositoryInterface;
use App\Services\GitHub\GitHubConnector;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GitHubConnectorTest extends TestCase
{
    #[Test]
    public function testViewReturnsExpectedCommitData(): void
    {
        $_GET['page'] = 2;

        $repositoryMock = $this->createMock(CommitRepositoryInterface::class);
        $repositoryMock->method('getByProviderGroupedByAuthor')
            ->willReturn([
                'Chris Cornell' => [['commit_message' => 'Test Commit']]
            ]);

        $repositoryMock->method('countByProvider')
            ->willReturn(150);

        $connector = new GitHubConnector('test-owner', 'test-repo', $repositoryMock);

        $result = $connector->view();

        $this->assertSame(2, $result['page']);
        $this->assertSame(2, $result['totalPages']);
        $this->assertSame(150, $result['totalCommits']);
        $this->assertArrayHasKey('Chris Cornell', $result['commits']);
    }

    #[Test]
    public function testGetFetchesAndSavesCommits(): void
    {
        $owner = 'soundgarden';
        $repo = 'superunknown';
        $count = 2;
        $perPage = 2;

        $now = new DateTimeImmutable('2025-06-08T00:00:00Z');

        $rawCommits = [
            [
                'sha' => 'abc123',
                'commit' => [
                    'author' => [
                        'name' => 'Chris Cornell',
                        'date' => '2025-06-08T03:46:12Z',
                    ],
                    'message' => 'Black Hole Sun',
                ],
                'author' => [
                    'avatar_url' => 'https://avatars.githubusercontent.com/u/chris',
                    'html_url' => 'https://github.com/chris',
                ],
                'html_url' => 'https://github.com/soundgarden/superunknown/commit/abc123',
            ],
            [
                'sha' => 'def456',
                'commit' => [
                    'author' => [
                        'name' => 'Kim Thayil',
                        'date' => '2025-06-07T01:00:00Z',
                    ],
                    'message' => 'Spoonman riff',
                ],
                'author' => [
                    'avatar_url' => 'https://avatars.githubusercontent.com/u/kim',
                    'html_url' => 'https://github.com/kim',
                ],
                'html_url' => 'https://github.com/soundgarden/superunknown/commit/def456',
            ],
        ];

        $expectedCommits = [
            [
                'provider' => 'github',
                'owner' => $owner,
                'repo' => $repo,
                'hash' => 'abc123',
                'author' => 'Chris Cornell',
                'author_avatar_url' => 'https://avatars.githubusercontent.com/u/chris',
                'author_html_url' => 'https://github.com/chris',
                'commit_date' => '2025-06-08T03:46:12Z',
                'commit_message' => 'Black Hole Sun',
                'commit_html_url' => 'https://github.com/soundgarden/superunknown/commit/abc123',
                'created_at' => $this->isInstanceOf(DateTimeImmutable::class),
                'updated_at' => $this->isInstanceOf(DateTimeImmutable::class),
            ],
            [
                'provider' => 'github',
                'owner' => $owner,
                'repo' => $repo,
                'hash' => 'def456',
                'author' => 'Kim Thayil',
                'author_avatar_url' => 'https://avatars.githubusercontent.com/u/kim',
                'author_html_url' => 'https://github.com/kim',
                'commit_date' => '2025-06-07T01:00:00Z',
                'commit_message' => 'Spoonman riff',
                'commit_html_url' => 'https://github.com/soundgarden/superunknown/commit/def456',
                'created_at' => $this->isInstanceOf(DateTimeImmutable::class),
                'updated_at' => $this->isInstanceOf(DateTimeImmutable::class),
            ],
        ];

        $repoMock = $this->createMock(CommitRepositoryInterface::class);
        $apiMock = $this->createMock(GitHubApi::class);

        $apiMock->expects($this->once())
            ->method('getRecentCommits')
            ->with($owner, $repo, $count, 1, $perPage)
            ->willReturn($rawCommits);

        $repoMock->expects($this->once())
            ->method('saveMany')
            ->with($this->callback(function ($commits) use ($expectedCommits): bool {
                return count($commits) === count($expectedCommits)
                    && $commits[0]['hash'] === $expectedCommits[0]['hash']
                    && $commits[1]['hash'] === $expectedCommits[1]['hash']
                    && $commits[0]['author'] === 'Chris Cornell'
                    && $commits[1]['author'] === 'Kim Thayil'
                    && $commits[0]['created_at'] instanceof DateTimeImmutable
                    && $commits[1]['created_at'] instanceof DateTimeImmutable;
            }));

        $connector = new GitHubConnector($owner, $repo, $repoMock, $apiMock);

        $result = $connector->get($count);

        $this->assertCount(2, $result);
        $this->assertEquals('abc123', $result[0]['hash']);
        $this->assertEquals('def456', $result[1]['hash']);
    }
}
