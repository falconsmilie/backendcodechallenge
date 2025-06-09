<?php

namespace Tests\Unit\Services\GitHub;

use App\Contracts\CommitGetInterface;
use App\Contracts\CommitSaveInterface;
use App\Contracts\CommitViewInterface;
use App\Services\GitHub\GitHubConnector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GitHubConnectorTest extends TestCase
{
    #[Test]
    public function testGetFetchesFormatsAndSavesCommits(): void
    {
        $mockGetter = $this->createMock(CommitGetInterface::class);
        $mockSaver = $this->createMock(CommitSaveInterface::class);
        $mockViewer = $this->createMock(CommitViewInterface::class);

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
            ]
        ];

        $mockGetter->expects($this->once())
            ->method('mostRecentCommits')
            ->with(100, 1, 100)
            ->willReturn($rawCommits);

        $mockSaver->expects($this->once())
            ->method('saveMany')
            ->with($this->callback(function (array $commits) {
                return $commits[0]['hash'] === 'abc123'
                    && $commits[0]['author'] === 'Chris Cornell'
                    && $commits[0]['commit_message'] === 'Black Hole Sun';
            }));

        $connector = new GitHubConnector(
            $mockGetter,
            $mockSaver,
            $mockViewer,
            'soundgarden',
            'superunknown'
        );

        $result = $connector->get();

        $this->assertIsArray($result);
        $this->assertSame('abc123', $result[0]['hash']);
        $this->assertSame('Chris Cornell', $result[0]['author']);
        $this->assertSame('Black Hole Sun', $result[0]['commit_message']);
        $this->assertSame('https://github.com/soundgarden/superunknown/commit/abc123', $result[0]['commit_html_url']);
    }

    #[Test]
    public function testViewReturnsPaginatedCommitsGroupedByAuthor(): void
    {
        $mockGetter = $this->createMock(CommitGetInterface::class);
        $mockSaver = $this->createMock(CommitSaveInterface::class);
        $mockViewer = $this->createMock(CommitViewInterface::class);

        $page = 2;
        $perPage = 5;
        $provider = 'github';
        $owner = 'nirvana';
        $repo = 'nevermind';

        $groupedCommits = [
            'Kurt Cobain' => [
                ['commit_message' => 'Smells Like Teen Spirit'],
                ['commit_message' => 'Come As You Are'],
            ],
            'Dave Grohl' => [
                ['commit_message' => 'Lithium'],
            ],
        ];

        $total = 13;

        $mockViewer->expects($this->once())
            ->method('getByProviderGroupedByAuthor')
            ->with($page, $perPage, $provider, $owner, $repo)
            ->willReturn($groupedCommits);

        $mockViewer->expects($this->once())
            ->method('countByProvider')
            ->with($provider, $owner, $repo)
            ->willReturn($total);

        $connector = new GitHubConnector(
            $mockGetter,
            $mockSaver,
            $mockViewer,
            $owner,
            $repo
        );

        $result = $connector->view($page, $perPage);

        $this->assertSame($groupedCommits, $result['commits']);
        $this->assertSame($page, $result['page']);
        $this->assertSame((int)ceil($total / $perPage), $result['totalPages']);
        $this->assertSame($total, $result['totalCommits']);
    }
}
