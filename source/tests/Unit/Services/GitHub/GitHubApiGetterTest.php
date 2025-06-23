<?php

namespace Tests\Unit\Services\GitHub;

use App\Api\GitHub\GitHubApi;
use App\Exceptions\CommitApiException;
use App\Services\GitHub\GitHubApiGetter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GitHubApiGetterTest extends TestCase
{
    private GitHubApi&MockObject $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = $this->createMock(GitHubApi::class);
    }

    public function testMostRecentCommitsReturnsFilteredCommitsWithShaOnly(): void
    {
        $this->api
            ->expects($this->exactly(2))
            ->method('mostRecentCommits')
            ->willReturnCallback(function ($owner, $repo, $page, $perPage) {
                if ($page === 1) {
                    return [
                        ['sha' => 'abc123', 'commit' => []],
                        ['sha' => 'def456', 'commit' => []],
                    ];
                }

                if ($page === 2) {
                    return [
                        ['commit' => []], // missing sha, should be ignored
                        ['sha' => 'ghi789', 'commit' => []],
                    ];
                }

                return [];
            });

        $getter = new GitHubApiGetter($this->api, 'nirvana', 'nevermind');

        $result = $getter->mostRecentCommits(4, 2, 2);

        $this->assertCount(3, $result);
        $this->assertEquals('abc123', $result[0]['sha']);
        $this->assertEquals('def456', $result[1]['sha']);
        $this->assertEquals('ghi789', $result[2]['sha']);
    }

    public function testMostRecentCommitsStopsIfFewerThanPerPageReturned(): void
    {
        $this->api->expects($this->once())
            ->method('mostRecentCommits')
            ->with('aliceinchains', 'dirt', 1, 3)
            ->willReturn([
                ['sha' => 'sha1'], ['sha' => 'sha2']
            ]); // only 2 commits < perPage (3), should break loop

        $getter = new GitHubApiGetter($this->api, 'aliceinchains', 'dirt');

        $result = $getter->mostRecentCommits(4, 3, 3); // asks for 3 pages, should only hit once

        $this->assertCount(2, $result);
    }

    public function testMostRecentCommitsThrowsWhenApiFails(): void
    {
        $this->expectException(CommitApiException::class);

        $this->api->method('mostRecentCommits')
            ->willThrowException(new CommitApiException('API failure'));

        $getter = new GitHubApiGetter($this->api, 'pearljam', 'ten');
        $getter->mostRecentCommits(10, 1, 10);
    }
}
