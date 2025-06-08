<?php

namespace Tests\Unit\Services\GitHub;

use App\Jobs\GitHub\FetchCommitsJob;
use App\Repositories\MySqlCommitRepository;
use App\Services\GitHub\GitHubService;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GitHubConnectorTest extends TestCase
{
    private Client $clientMock;
    private MySqlCommitRepository $repositoryMock;
    private GitHubService $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = $this->createMock(Client::class);
        $this->repositoryMock = $this->createMock(MySqlCommitRepository::class);

        $this->connector = new GitHubService(
            $this->clientMock,
            $this->repositoryMock,
            'octocat',
            'hello-world'
        );
    }

    #[Test]
    public function view_returns_paginated_commit_data(): void
    {
        $_GET['page'] = 2;

        $this->repositoryMock->expects(self::once())
            ->method('getByProviderGroupedByAuthor')
            ->with(
                2,
                100,
                'github',
                'octocat',
                'hello-world'
            )
            ->willReturn(['alice' => [['hash' => 'abc123']]]);

        $this->repositoryMock->expects(self::once())
            ->method('countByProvider')
            ->with('github', 'octocat', 'hello-world')
            ->willReturn(250);

        $result = $this->connector->view();

        self::assertSame(2, $result['page']);
        self::assertSame(250, $result['totalCommits']);
        self::assertSame(3, $result['totalPages']);
        self::assertSame(['alice' => [['hash' => 'abc123']]], $result['commits']);
    }

    #[Test]
    public function view_defaults_to_page_1_if_not_set(): void
    {
        unset($_GET['page']);

        $this->repositoryMock->expects(self::once())
            ->method('getByProviderGroupedByAuthor')
            ->with(
                1,
                100,
                'github',
                'octocat',
                'hello-world'
            )
            ->willReturn([]);

        $this->repositoryMock->expects(self::once())
            ->method('countByProvider')
            ->willReturn(0);

        $result = $this->connector->view();

        self::assertSame(1, $result['page']);
        self::assertSame(0, $result['totalCommits']);
        self::assertSame(0, $result['totalPages']);
        self::assertSame([], $result['commits']);
    }

    #[Test]
    public function get_invokes_fetch_commits_job_and_returns_result(): void
    {
        $expected = [['hash' => 'abc123']];

        if (!function_exists('App\Services\GitHub\config')) {
            function config(string $key, mixed $default = null): mixed
            {
                return 100;
            }
        }

        $job = $this->getMockBuilder(FetchCommitsJob::class)
            ->setConstructorArgs([$this->clientMock, 100])
            ->onlyMethods(['handle'])
            ->getMock();

        $job->expects(self::once())
            ->method('handle')
            ->with(
                'github',
                'octocat',
                'hello-world',
                100
            )
            ->willReturn($expected);

        // Use reflection to override new job creation
        $connector = new class($this->clientMock, $this->repositoryMock, 'octocat', 'hello-world', $job) extends GitHubService {
            public function __construct(
                $client,
                $commits,
                string $owner,
                string $repo,
                private FetchCommitsJob $jobMock
            ) {
                parent::__construct($client, $commits, $owner, $repo);
            }

            public function get(int $count = 100): array
            {
                return $this->jobMock->handle(
                    'github',
                    $this->owner,
                    $this->repo
                );
            }
        };

        $result = $connector->get(100);

        self::assertSame($expected, $result);
    }
}
